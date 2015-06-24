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
     * Main Config
     *
     * @var null|\Zend\Config\Config
     */
    protected $mainConfig;

    /**
     * Constructor
     *
     * @param \VuFind\Record\Loader $loader     Record loader
     * @param \Zend\Config\Config   $mainConfig VuFind main configuration (omit for
     * built-in defaults)
     */
    public function __construct(\VuFind\Record\Loader $loader, $mainConfig = null)
    {
        $this->recordLoader = $loader;
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

        // due to section naming changes in DAIA.ini switch legacySupport
        if ($this->legacySupport) {
            // set the ILS-specific recordId for interaction with ILS

            // get the ILS-specific identifier
            if (!isset($this->config['Global']['ilsIdentifier'])) {
                $this->debug(
                    "No ILS-specific identifier configured, setting ilsIdentifier=default."
                );
                $this->ilsIdentifier = "default";
            } else {
                $this->ilsIdentifier = $this->config['Global']['ilsIdentifier'];
            }

            // get ISIL from config if ILS-specific recordId is barcode for
            // interaction with ILS
            if (!isset($this->mainConfig['InstitutionInfo']['isil'])) {
                $this->debug("No ISIL defined in section InstitutionInfo in config.ini.");
                $this->isil = '';
            } else {
                $this->isil = $this->mainConfig['InstitutionInfo']['isil'];
            }
        } else {
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

            // get ISIL from config if ILS-specific recordId is barcode for
            // interaction with ILS
            if (!isset($this->mainConfig['InstitutionInfo']['isil'])) {
                $this->debug("No ISIL defined in section InstitutionInfo in config.ini.");
                $this->isil = '';
            } else {
                $this->isil = $this->mainConfig['InstitutionInfo']['isil'];
            }
        }

        $this->_testILSConnections();
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
        if ($this->checkForILSTestId($id)) {
            return [];
        }
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
            $ilsRecordId = $this->_getRecord($id)
                ->getILSIdentifier($this->ilsIdentifier);
            if ($ilsRecordId == '') {
                $this->_idMapper[$id] = $id;

                return $id;
            } else {
                if (is_array($ilsRecordId)) {
                    // use ISIL for identifying the correct ILS-identifier if
                    // array is returned
                    foreach ($ilsRecordId as $recordId) {
                        if (preg_match("/^(\(".$this->isil."\)).*$/", $recordId)) {
                            $recordId = substr(
                                $recordId,
                                strpos($recordId, "(".$this->isil.")")+strlen("(".$this->isil.")")
                            );
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
     * Private service test method
     *
     * @return void
     * @throws ILSException
     */
    private function _testILSConnections()
    {
        try {
            // test DAIA service
            $this->httpService->get(
                substr(
                    $this->baseUrl,
                    0,
                    strrpos($this->baseUrl, "/", strrpos($this->baseUrl, "/"))
                )
            );
            // test PAIA service
            $this->httpService->get(
                substr(
                    $this->paiaURL,
                    0,
                    strrpos(
                        $this->paiaURL,
                        "/",
                        strrpos($this->paiaURL, "/", strrpos($this->paiaURL, "/"))
                    )
                )
            );
        } catch (\Exception $e) {
            throw new ILSException($e->getMessage());
        }
    }
}