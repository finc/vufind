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
 * @category VuFind2
 * @package  ILS_Drivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
namespace finc\ILS\Driver;
use VuFind\Exception\ILS as ILSException,
    VuFindSearch\Query\Query, VuFindSearch\Service as SearchService,
    Zend\Log\LoggerAwareInterface as LoggerAwareInterface;

/**
 * Finc specific ILS Driver for VuFind, using PAIA and DAIA services.
 *
 * @category VuFind2
 * @package  ILS_Drivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
class FincILS extends PAIA implements LoggerAwareInterface
{

    private $_username;
    private $_password;
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
            $this->_username = $username;
            $this->_password = $password;

            try {
                return $this->paiaRootLogin($username, $password);
            } catch (ILSException $e) {
                throw new ILSException($e->getMessage());
            }
        } else {
            return parent::patronLogin($username, $password);
        }
    }

    /**
     * Private authentication function - use PAIA root credentials for authentication
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return mixed Associative array of patron info on successful login,
     * null on unsuccessful login, PEAR_Error on error.
     * @throws ILSException
     */
    protected function paiaRootLogin($username, $password)
    {
        $post_data = [
            "username" => $this->_root_username,
            "password" => $this->_root_password,
            "grant_type" => "password",
            "scope" => "read_patron read_fees read_items write_items change_password"
        ];
        $responseJson = $this->paiaPostRequest('auth/login', $post_data);

        try {
            $responseArray = $this->paiaParseJsonAsArray($responseJson);
        } catch (ILSException $e) {
            if ($e->getMessage() === 'access_denied') {
                return null;
            }
            throw new ILSException(
                $e->getCode() . ':' . $e->getMessage()
            );
        }

        if (array_key_exists('access_token', $responseArray)) {
            $_SESSION['paiaToken'] = $responseArray['access_token'];
            if (array_key_exists('patron', $responseArray)) {
                if ($responseArray['patron'] === 'root') {
                    $patron = $this->paiaGetUserDetails($username);
                    $patron['cat_username'] = $username;
                    $patron['cat_password'] = $password;
                } else {
                    $patron = $this->paiaGetUserDetails($responseArray['patron']);
                    $patron['cat_username'] = $responseArray['patron'];
                    $patron['cat_password'] = $password;
                }
                return $patron;
            } else {
                throw new ILSException(
                    'Login credentials accepted, but got no patron ID?!?'
                );
            }
        } else {
            throw new ILSException('Unknown error! Access denied.');
        }
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
     * @param string $id Document to look up.
     *
     * @return string $ilsRecordId
     */
    private function _getILSRecordId($id)
    {
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
     * @param array $ids Documents to look up.
     *
     * @return array $ilsRecordIds
     */
    private function _getILSRecordIds($ids)
    {
        $ilsRecordIds = [];

        if (is_array($ids)) {
            foreach ($ids as $id) {
                $ilsRecordIds[] = $this->_getILSRecordId($id);
            }

            return $ilsRecordIds;
        }

        return $ids;
    }

    /**
     * Get the finc id of the record with the given ilsIdentifier value
     *
     * @param string $ilsId Document to look up.
     *
     * @return string $fincId if ilsIdentifier is configured, otherwise $ilsId
     */
    private function _getFincId($ilsId)
    {
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