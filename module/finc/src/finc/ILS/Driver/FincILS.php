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

    private $_root_username;
    private $_root_password;

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
    public function __construct(\VuFind\Date\Converter $converter,
        \VuFind\Record\Loader $loader, SearchService $ss, $mainConfig = null
    ) {
        $this->dateConverter = $converter;
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
            $this->_root_username = $this->config['PAIA']['root_username'];
            $this->_root_password = $this->config['PAIA']['root_password'];
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
     * PAIA support method - try to find fincid for last segment of PAIA id
     *
     * @param string $id itemId
     *
     * @return string $id
     */
    protected function getAlternativeItemId($id)
    {
        return $this->_getFincId(end(explode(":", $id)));
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
        if (!empty($this->_root_username) && !empty($this->_root_password)) {
            if ($username == '') {
                throw new ILSException('Invalid Login, Please try again.');
            }

            $enrichUserDetails = function ($details, $username, $password) {
                $details['cat_username']
                    = ($this->session->patron === 'root' ? $username : $this->session->patron);
                $details['cat_password'] = $password;
                return $details;
            };

            // if we already have a session with access_token and patron id, try to get
            // patron info with session data
            if (isset($this->session->expires) && $this->session->expires > time()) {
                try {
                    return $enrichUserDetails(
                        $this->paiaGetUserDetails(($this->session->patron === 'root' ? $username : $this->session->patron)),
                        $username,
                        $password
                    );
                } catch (ILSException $e) {
                    $this->debug('Session expired, login again', 'info');
                }
            }

            try {
                if($this->paiaLogin($this->_root_username, $this->_root_password)) {
                    return $enrichUserDetails(
                        $this->paiaGetUserDetails(($this->session->patron === 'root' ? $username : $this->session->patron)),
                        $username,
                        $password
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
                                    if (!(isset($doc[$excludeKey])
                                        && in_array($doc[$excludeKey], (array) $excludeValue))
                                    ) {
                                        $excludeCounter++;
                                    }
                                }
                                if ($excludeCounter == count($filterValue)) {
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
                                if ($regexCounter == count($filterValue)) {
                                    $filterCounter++;
                                }
                                break;
                            default:
                                if (isset($doc[$filterKey])
                                    && in_array($doc[$filterKey], (array) $filterValue)
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
        // override ilsIdentifier set in ILS driver config
        if ($ilsIdentifier != null) {
            $this->ilsIdentifier = $ilsIdentifier;
        }

        //get the ILS-specific recordId
        if ($this->ilsIdentifier != "default") {

            try {
                $ilsRecordId = $this->_getRecord($id)
                    ->getILSIdentifier($this->ilsIdentifier);
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
        // override ilsIdentifier set in ILS driver config
        if ($ilsIdentifier != null) {
            $this->ilsIdentifier = $ilsIdentifier;
        }

        if ($this->ilsIdentifier != "default") {
            // different ilsIdentifier is configured, retrieve fincid
            try {
                $query = $this->ilsIdentifier . ':' . $ilsId;
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