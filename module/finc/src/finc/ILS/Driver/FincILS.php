<?php
/**
 * Finc specific ILS Driver for VuFind, using PAIA and DAIA services.
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2015.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace finc\ILS\Driver;
use VuFind\Exception\ILS as ILSException,
    VuFindSearch\Query\Query, VuFindSearch\Service as SearchService,
    Zend\Log\LoggerAwareInterface as LoggerAwareInterface;

/**
 * Finc specific ILS Driver for VuFind, using PAIA and DAIA services.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class FincILS extends PAIA implements LoggerAwareInterface
{

    protected $root_username;
    protected $root_password;

    /**
     * Array that stores the mapping of VuFind record_id to the ILS-specific
     * identifier retrieved by @_getILSRecordId()
     *
     * @var array
     */
    private $_idMapper = [];

    /**
     * Identifier used for interaction with ILS
     *
     * @var string
     */
    protected $ilsIdentifier;

    /**
     * Array containing known dynamic fields that need to be extended by
     * indexExtension if used for search
     *
     * @var array
     */
    protected $dynamicFields = ['barcode'];

    /**
     * ISIL used for identifying the correct ILS-identifier if array is returned
     *
     * @var string
     */
    protected $isil;

    /**
     * Record loader
     *
     * @var \VuFind\Record\Loader
     */
    protected $recordLoader;

    /**
     * Connection used when searching for fincid
     *
     * @var SearchService
     */
    protected $searchService;

    /**
     * Date converter object
     *
     * @var \VuFind\Date\Converter
     */
    protected $dateConverter;

    /**
     * Main Config
     *
     * @var null|\Zend\Config\Config
     */
    protected $mainConfig;

    /**
     * Constructor
     *
     * @param \VuFind\Date\Converter $converter  Date converter
     * @param \VuFind\Record\Loader  $loader     Record loader
     * @param \Zend\Config\Config    $mainConfig VuFind main configuration (omit for
     * built-in defaults)
     */
    public function __construct(\VuFind\Date\Converter $converter, \Zend\Session\SessionManager $sessionManager,
        \VuFind\Record\Loader $loader, SearchService $ss, $mainConfig = null
    ) {
        parent::__construct($converter, $sessionManager);
        $this->recordLoader = $loader;
        $this->searchService = $ss;
        $this->mainConfig = $mainConfig;
    }

    /**
     * Initialize the driver.
     *
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     *
     * @throws ILSException
     * @return void
     */
    public function init()
    {
        parent::init();

        // set the ILS-specific recordId for interaction with ILS

        // get the ILS-specific identifier
        if (!isset($this->config['DAIA']['ilsIdentifier'])) {
            $this->debug(
                "No ILS-specific identifier configured, setting ilsIdentifier=default."
            );
            $this->ilsIdentifier = "default";
        } else {
            $this->ilsIdentifier = $this->config['DAIA']['ilsIdentifier'];
        }

        // get PAIA root credentials if configured
        if (isset($this->config['PAIA']['root_username'])
            && isset($this->config['PAIA']['root_username'])
        ) {
            $this->root_username = $this->config['PAIA']['root_username'];
            $this->root_password = $this->config['PAIA']['root_password'];
        }

        // get ISIL from config if ILS-specific recordId is barcode for
        // interaction with ILS
        if (!isset($this->mainConfig['InstitutionInfo']['isil'])) {
            $this->debug("No ISIL defined in section InstitutionInfo in config.ini.");
            $this->isil = [];
        } else {
            $this->isil = $this->mainConfig['InstitutionInfo']['isil']->toArray();
        }

        $this->_testILSConnections();
    }

    /**
     * Check if email hold is valid
     *
     * This is responsible for determining if an item is requestable
     *
     * @param string $id     The Bib ID
     * @param array  $data   An Array of item data
     * @param patron $patron An array of patron data
     *
     * @return bool True if request is valid, false if not
     */
    public function checkEmailHoldIsValid($id, $data, $patron)
    {
        // without item_id we cannot check if the item is available for email holding
        if (!isset($data['item_id'])) {
            return false;
        }

        // get status information
        $status = $this->getStatus($id);
        foreach ($status as $item) {
            // search for status information for given item_id
            if (isset($item['item_id']) && $item['item_id'] == $data['item_id']) {
                return $this->checkEmailHoldValidationCriteria($item);
            }
        }

        // if we have come so far no criteria matched and email holds are not allowed
        return false;
    }

    /**
     * Helper for checking the given item for the configured Email Hold validation
     * criteria
     *
     * @param $item
     * @return bool
     */
    protected function checkEmailHoldValidationCriteria($item)
    {
        $criteria = $this->getEmailHoldValidationCriteria();
        foreach($criteria as $key => $value) {
            if (isset($item[$key])
                && ((is_array($item[$key]) && in_array($value, $item[$key]))
                    || ($value == $item[$key]))
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns whether hold should be placed via Email for the current item based on
     * settings in FincILS.ini.
     *
     * @param $item
     * @return array
     */
    protected function getEmailHoldValidationCriteria()
    {
        $criteria = [];
        if (isset($this->config['EmailHold']['emailHoldValidationCriteria'])) {
            foreach ($this->config['EmailHold']['emailHoldValidationCriteria'] as $value) {
                $criteria[
                explode('::', $value)[0]
                ] = explode('::', $value)[1];
            }
        }
        return $criteria;
    }

    /**
     * Get Pick Up Locations
     *
     * This is responsible for gettting a list of valid library locations for
     * holds / recall retrieval
     *
     * @param array $patron      Patron information returned by the patronLogin
     *                           method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing a hold; contains most of the same values passed to
     * placeHold, minus the patron data.  May be used to limit the pickup options
     * or may be ignored.  The driver must not add new options to the return array
     * based on this data or other areas of VuFind may behave incorrectly.
     *
     * @return array        An array of associative arrays with locationID and
     * locationDisplay keys
     */
    public function getPickupLocations($patron = null, $holdDetails = null)
    {
        if (isset($details['id']) && isset($details['item_id'])) {
            // getHolding information for given item_id
            $info = $this->getHolding($details['id']);
            // now extract pickupLocations for each returned item
            foreach ($info as $item) {
                if (isset($item['item_id'])
                    && $item['item_id'] == $details['item_id']
                ) {
                    return isset($item['location'])
                        ? [[
                            'locationID' =>
                                ($item['locationid']!=''
                                    ? $item['locationid']
                                    : $item['location']
                                ),
                            'locationDisplay' => $item['location']
                        ]]
                        : [];
                }
            }
        }
        return [];
    }
    
    /*********************************************
     * Custom DAIA methods
     *********************************************/

    /**
     * PAIA support method - try to find fincid for last segment of PAIA id
     *
     * @param string $id     itemId
     * @param string $idType id type to override ILS settings
     *
     * @return string $id
     */
    protected function getAlternativeItemId($id, $idType = null)
    {
        return $this->_getFincId(end(explode(":", $id)), $idType);
    }

    /**
     * Get Status
     *
     * Wrapper implementation of @getStatus($id) to retrieve the status
     * information of a certain record by using ILS-specific identifier.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatus($id)
    {
        return $this->_replaceILSId(
            parent::getStatus($this->_getILSRecordId($id)), $id
        );
    }

    /**
     * Get Statuses
     *
     * Wrapper implementation of @getStatuses($id) to retrieve status
     * information for several records by using ILS-specific identifier.
     *
     * @param array $ids The array of record ids to retrieve the status for
     *
     * @return array    An array of status information values on success.
     */
    public function getStatuses($ids)
    {
        return $this->_replaceILSIds(
            parent::getStatuses($this->_getILSRecordIds($ids)), $ids
        );
    }
    
    /**
     * Override and add to DAIA item status Email Hold availability
     *
     * @param array $item
     * @return array
     *
     * Todo: use $return['addEmailHoldLink'] = 'check'; for patron based service
     * availability
     */
    protected function getItemStatus($item)
    {
        $return = parent::getItemStatus($item);
        $return['addEmailHoldLink'] = $this->checkEmailHoldValidationCriteria($return);
        if ($return['addEmailHoldLink'] == true) {
            $return['addLink'] = false;
        }
        return $return;
    }
    
    /**
     * Returns the value for "barcode" in VuFind getStatus/getHolding array
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemBarcode($item)
    {
        if (isset($item['id']) && preg_match("/^".$this->daiaIdPrefix."([A-Za-z0-9]+):([A-Za-z0-9]+)$/", $item['id'], $matches)) {
            return array_pop($matches);
        }
        return parent::getItemBarcode($item);
    }

    /*********************************************
     * Custom PAIA methods
     *********************************************/

    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $username The patron's username
     * @param string $password The patron's login password
     *
     * @return mixed          Associative array of patron info on successful login,
     * null on unsuccessful login.
     *
     * @throws ILSException
     */
    public function patronLogin($username, $password)
    {
        if (!empty($this->root_username) && !empty($this->root_password)) {
            if ($username == '') {
                throw new ILSException('Invalid Login, Please try again.');
            }

            $session = $this->getSession();

            // if we already have a session with access_token and patron id, try to get
            // patron info with session data
            if (isset($session->expires) && $session->expires > time()) {
                try {
                    return $this->enrichUserDetails(
                        $this->paiaGetUserDetails(($session->patron === 'root' ? $username : $session->patron)),
                        $password,
                        $username
                    );
                } catch (ILSException $e) {
                    $this->debug('Session expired, login again', 'info');
                }
            }

            try {
                if($this->paiaLogin($this->root_username, $this->root_password)) {
                    return $this->enrichUserDetails(
                        $this->paiaGetUserDetails(($session->patron === 'root' ? $username : $session->patron)),
                        $password,
                        $username
                    );
                }
            } catch (ILSException $e) {
                throw new ILSException($e->getMessage());
            }
        } else {
            return parent::patronLogin($username, $password);
        }
    }
    
    /**
     * PAIA helper function to map session data to return value of patronLogin()
     *
     * @param $details  Patron details returned by patronLogin
     * @param $password Patron cataloge password
     * @return mixed
     */
    protected function enrichUserDetails($details, $password, $username = null)
    {
        $details = parent::enrichUserDetails($details, $password);

        // overwrite cat_username if we logged in as root
        $session = $this->getSession();
        $details['cat_username'] = $session->patron === 'root' && !empty($username)
            ? $username : $session->patron;
        
        return $details;
    }
    
    /**
     * Customized PAIA support method for PAIA core method 'items' returning only
     * filtered items.
     * Available filters:
     *      - key=>value : PAIA document.key must contain value
     *      - exclude => [key=>value] : PAIA document.key must not contain value
     *      - regex => [key=>value] : PAIA document.key must preg_match(value)
     *
     * @param array $patron Array with patron information
     * @param array $filter Array of properties identifying the wanted items
     *
     * @return array|mixed Array of documents containing the given filter properties
     */
    protected function paiaGetItems($patron, $filter = [])
    {
        $itemsResponse = $this->paiaGetAsArray(
            'core/'.$patron['cat_username'].'/items'
        );

        if (isset($itemsResponse['doc'])) {
            if (count($filter)) {
                $filteredItems = [];
                foreach ($itemsResponse['doc'] as $doc) {
                    $filterCounter = 0;
                    foreach ($filter as $filterKey => $filterValue) {
                        switch ($filterKey) {
                            case 'exclude' :
                                // check exclude filters
                                $excludeCounter = 0;
                                foreach ($filterValue as $excludeKey => $excludeValue) {
                                    if ((isset($doc[$excludeKey]) && in_array($doc[$excludeKey], (array) $excludeValue))
                                        || ($excludeValue == null && !isset($doc[$excludeKey]))
                                    ) {
                                        $excludeCounter++;
                                    }
                                }
                                // exclude is a negative filter, so the item might be
                                // selected if exclude does NOT match
                                if ($excludeCounter != count($filterValue)) {
                                    $filterCounter++;
                                }
                                break;
                            case 'regex' :
                                // check regex filters
                                $regexCounter = 0;
                                foreach ($filterValue as $regexField => $regexPattern) {
                                    if (isset($doc[$regexField])
                                        && preg_match($regexPattern, $doc[$regexField]) === 1
                                    ) {
                                        $regexCounter++;
                                    }
                                }
                                // regex is a positive filter, so the item might be
                                // selected if regex does match
                                if ($regexCounter == count($filterValue)) {
                                    $filterCounter++;
                                }
                                break;
                            default:
                                // any other filter is a positive filter, so the item
                                // might be selected if the key-value pair does match
                                if ((isset($doc[$filterKey]) && in_array($doc[$filterKey], (array) $filterValue))
                                    || ($filterValue == null && !isset($doc[$filterKey]))
                                ) {
                                    $filterCounter++;
                                }
                                break;
                        }
                    }
                    // check if all filters applied
                    if ($filterCounter == count($filter)) {
                        $filteredItems[] = $doc;
                    }
                }
                return $filteredItems;
            } else {
                return $itemsResponse;
            }
        } else {
            $this->debug(
                "No documents found in PAIA response. Returning empty array."
            );
        }
        return [];
    }

    /**
     * PAIA helper function to allow customization of mapping from PAIA response to
     * VuFind ILS-method return values.
     *
     * @param array  $items   Array of PAIA items to be mapped
     * @param string $mapping String identifying a custom mapping-method
     *
     * @return array
     */
    protected function mapPaiaItems($items, $mapping)
    {
        return $this->postprocessPaiaItems(
            parent::mapPaiaItems($items, $mapping)
        );
    }

    /**
     * Helper function to postprocess the PAIA items for display in catalog (e.g. retrieve
     * fincid etc.).
     *
     * @param array $items Array of PAIA items to be postprocessed
     *
     * @return mixed
     */
    protected function postprocessPaiaItems($items)
    {
        // regex pattern for item_id (e.g. UBL:barcode:0008911555)
        $idPattern = '/^([]A-Za-z0-9_\-]*):(%s):(.*)$/';

        // item_id identifier - Solr field mapping
        $identifier = [
            'barcode' => 'barcode' .
                (isset($this->mainConfig->CustomIndex->indexExtension)
                    ? '_'.$this->mainConfig->CustomIndex->indexExtension : ''),
            'fincid'  => 'id',
            'ppn'     => 'record_id'
        ];

        // try item_id with defined regex pattern and identifiers and use Solr to
        // retrieve fincid on match
        $ilsIdentifier = function ($itemId) use ($identifier, $idPattern) {
            foreach ($identifier as $key => $value) {
                $matches = [];
                if (preg_match(sprintf($idPattern, $key), $itemId, $matches)) {
                    return $this->_getFincId($matches[3], $value);
                }
            }
        };

        // iterate trough given items
        foreach ($items as &$item) {
            if (isset($item['id']) && empty($item['id']) && !empty($item['item_id'])) {
                $ilsId = $ilsIdentifier($item['item_id']);
                if ($ilsId != null) {
                    $item['id'] = $ilsId;
                    $item['source'] = 'Solr';
                }
            }
        }

        return $items;
    }

    /*********************************************
     * Finc-ILS specific methods 
     *********************************************/
    
    /**
     * Get the Record-Object from the RecordDriver.
     *
     * @param string $id ID of record to retrieve
     *
     * @return \VuFind\RecordDriver\AbstractBase
     */
    private function _getRecord($id)
    {
        return $this->recordLoader->load($id);
    }

    /**
     * Function to replace the custom ILS specific identifier
     * with the VuFind record_id, provided by the mapping array
     * $idMapper.
     *
     * @param array  $array Array with status information.
     * @param string $id    VuFind record_id.
     *
     * @return mixed
     */
    private function _replaceILSId($array, $id)
    {
        $statuses = [];
        foreach ($array as $status) {
            if (isset($status['item_id'])
                && $status['item_id'] == $this->_idMapper[$id]
            ) {
                $status['item_id'] = $id;
            }
            if (isset($status['id'])
                && $status['id'] == $this->_idMapper[$id]
            ) {
                $status['id'] = $id;
            }
            $statuses[] = $status;
        }

        return $statuses;
    }

    /**
     * Function to replace the custom ILS specific identifier
     * with the VuFind record_id in several status information
     * arrays.
     *
     * @param array $array Array with status information from several records.
     *                          records.
     * @param array $ids   Array with VuFind record_ids.
     *
     * @return array
     */
    private function _replaceILSIds($array, $ids)
    {
        $results = [];
        foreach ($array as $statuses) {
            foreach ($ids as $id) {
                if ($this->_containsILSid($statuses, $id)) {
                    // save the result if _replaceILSId had some effect
                    $results[] = $this->_replaceILSId($statuses, $id);
                }
            }
        }

        return $results;
    }

    /**
     * Function to check whether the given array with status information
     * contains an ILS-specific identifier, provided by idMapper($id)
     *
     * @param array  $array Array with status information.
     * @param string $id    VuFind record_id.
     *
     * @return bool
     */
    private function _containsILSid($array, $id)
    {
        foreach ($array as $status) {
            if ($status['item_id'] == $this->_idMapper[$id]
                || $status['id'] == $this->_idMapper[$id]
            ) {

                return true;
            }
        }

        return false;
    }

    /**
     * Get the identifier for the record which will be used for ILS interaction
     *
     * @param string $id            Document to look up.
     * @param string $ilsIdentifier Identifier to override config settings.
     *
     * @return string $ilsRecordId
     */
    private function _getILSRecordId($id, $ilsIdentifier = null)
    {
        // override ilsIdentifier with the ilsIdentifier set in ILS driver config
        if ($ilsIdentifier == null) {
            $ilsIdentifier = $this->ilsIdentifier;
        }

        //get the ILS-specific recordId
        if ($ilsIdentifier != "default") {

            try {
                $ilsRecordId = $this->_getRecord($id)
                    ->getILSIdentifier($ilsIdentifier);
            } catch (\Exception $e) {
                $this->debug($e);
                $this->_idMapper[$id] = $id;
                return $id;
            }

            if ($ilsRecordId == '') {
                $this->_idMapper[$id] = $id;
                return $id;
            } else {
                if (is_array($ilsRecordId)) {
                    // use ISIL for identifying the correct ILS-identifier if
                    // array is returned
                    $isils = implode("|", $this->isil);
                    foreach ($ilsRecordId as $recordId) {
                        if (preg_match(
                            "/^\((" . $isils . ")\)(.*)$/", $recordId, $match
                        )
                        ) {
                            $recordId = (isset($match[2]) && strlen($match[2] > 0))
                                ? $match[2] : null;
                            $this->_idMapper[$id] = $recordId;
                            return $recordId;
                        }
                    }
                    // no match was found for the given ISIL, therefore return $id
                    return $id;
                }
                $this->_idMapper[$id] = $ilsRecordId;

                return $ilsRecordId;
            }
        }
        $this->_idMapper[$id] = $id;

        return $id;
    }

    /**
     * Get the identifiers for multiple records
     *
     * @param array  $ids           Documents to look up.
     * @param string $ilsIdentifier Identifier to override config settings.
     *
     * @return array $ilsRecordIds
     */
    private function _getILSRecordIds($ids, $ilsIdentifier = null)
    {
        $ilsRecordIds = [];

        // override ilsIdentifier with the ilsIdentifier set in ILS driver config
        if ($ilsIdentifier == null) {
            $ilsIdentifier = $this->ilsIdentifier;
        }

        if (is_array($ids)) {
            foreach ($ids as $id) {
                $ilsRecordIds[] = $this->_getILSRecordId($id, $ilsIdentifier);
            }

            return $ilsRecordIds;
        }

        return $ids;
    }

    /**
     * Get the finc id of the record with the given ilsIdentifier value
     *
     * @param string $ilsId         Document to look up.
     * @param string $ilsIdentifier Identifier to override config settings.
     *
     * @return string $fincId if ilsIdentifier is configured, otherwise $ilsId
     */
    private function _getFincId($ilsId, $ilsIdentifier = null)
    {
        // override ilsIdentifier with the ilsIdentifier set in ILS driver config
        if ($ilsIdentifier == null) {
            $ilsIdentifier = $this->ilsIdentifier;
        }

        if ($ilsIdentifier != "default") {
            // different ilsIdentifier is configured, retrieve fincid
            
            // if the given ilsIdentifier is known as a dynamic field it is suffixed
            // with the isil
            if (in_array($ilsIdentifier, $this->dynamicFields)) {
                if (isset($this->mainConfig->CustomIndex->indexExtension)) {
                    $ilsIdentifier .= "_"
                        . trim($this->mainConfig->CustomIndex->indexExtension);
                }
            }
            try {
                $query = $ilsIdentifier . ':' . $ilsId;
                $result = $this->searchService->search('VuFind', new Query($query));
                if (count($result) === 0) {
                    throw new \Exception(
                        'Problem retrieving finc id for record with '
                        . $this->ilsIdentifier . ":" . $ilsId
                    );
                }
                return current($result->getRecords())->getUniqueId();
            } catch (\Exception $e) {
                $this->debug($e);
                return $ilsId;
            }
        }
        return $ilsId;
    }

    /**
     * Private service test method
     *
     * @return void
     * @throws ILSException
     */
    private function _testILSConnections()
    {
        try {
            // test DAIA service
            preg_match(
                "/^(http[s:\/0-9\.]*(:[0-9]*)?\/[a-z]*)/",
                $this->baseUrl,
                $daiaMatches
            );
            $this->httpService->get($daiaMatches[1]);
            // test PAIA service
            preg_match(
                "/^(http[s:\/0-9\.]*(:[0-9]*)?\/[a-z]*)/",
                $this->paiaURL,
                $paiaMatches
            );
            $this->httpService->get($paiaMatches[1]);
        } catch (\Exception $e) {
            throw new ILSException($e->getMessage());
        }
    }
}