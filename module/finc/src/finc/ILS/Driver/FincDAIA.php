<?php
/**
 * ILS Driver for VuFind to query availability information via DAIA.
 *
 * Based on the proof-of-concept-driver by Till Kinstler, GBV.
 *
 * PHP version 5
 *
 * Copyright (C) Oliver Goldschmidt 2010.
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
 * @author   Oliver Goldschmidt <o.goldschmidt@tu-harburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
namespace finc\ILS\Driver;
use DOMDocument, VuFind\Exception\ILS as ILSException;

/**
 * ILS Driver for VuFind to query availability information via DAIA.
 *
 * Based on the proof-of-concept-driver by Till Kinstler, GBV.
 *
 * @category VuFind2
 * @package  ILS_Drivers
 * @author   Oliver Goldschmidt <o.goldschmidt@tu-harburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
class FincDAIA extends PAIA implements \Zend\Log\LoggerAwareInterface
{
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
     * Constructor
     *
     * @param \VuFind\Record\Loader $loader Record loader
     */
    public function __construct(\VuFind\Record\Loader $loader)
    {
        $this->recordLoader = $loader;
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

        // due to section naming changes in DAIA.ini
        // switch legacySupport
        if ($this->legacySupport) {
            // set the ILS-specific recordId for interaction with ILS
            // get the ILS-specific identifier
            if (!isset($this->config['Global']['ilsIdentifier'])) {
                $this->debug("No ILS-specific identifier configured, setting ilsIdentifier=default.");
                $this->ilsIdentifier = "default";
            } else {
                $this->ilsIdentifier = $this->config['Global']['ilsIdentifier'];
            }

            // get ISIL from config if ILS-specific recordId is barcode for interaction with ILS
            // get the ILS-specific identifier
            if (!isset($this->config['Global']['ISIL'])) {
                $this->debug("No ISIL for ILS-driver configured.");
                $this->isil = '';
            } else {
                $this->isil = $this->config['Global']['ISIL'];
            }
        } else {
            // set the ILS-specific recordId for interaction with ILS
            // get the ILS-specific identifier
            if (!isset($this->config['DAIA']['ilsIdentifier'])) {
                $this->debug("No ILS-specific identifier configured, setting ilsIdentifier=default.");
                $this->ilsIdentifier = "default";
            } else {
                $this->ilsIdentifier = $this->config['DAIA']['ilsIdentifier'];
            }

            // get ISIL from config if ILS-specific recordId is barcode for interaction with ILS
            // get the ILS-specific identifier
            if (!isset($this->config['DAIA']['ISIL'])) {
                $this->debug("No ISIL for ILS-driver configured.");
                $this->isil = '';
            } else {
                $this->isil = $this->config['DAIA']['ISIL'];
            }
        }

    }

    /**
     * Wrapper implementation of @queryDAIAXML($id) to perform the query
     * with the ILS-specific identifier.
     *
     * @param string $id Document to look up.
     *
     * @return DOMDocument Object representation of an XML document containing
     * content as described in the DAIA format specification.
     */
    protected function doHTTPRequest($id)
    {
        return parent::doHTTPRequest($this->getILSRecordId($id));
    }

    /**
     * Get the Record-Object from the RecordDriver.
     *
     * @param string $id ID of record to retrieve
     *
     * @return \VuFind\RecordDriver\AbstractBase
     */
    protected function getRecord($id)
    {
        return $this->recordLoader->load($id);
    }

    /**
     * Get the identifier for the record which will be used for ILS interaction
     *
     * @param string $id Document to look up.
     *
     * @return string $ilsRecordId
     */
    protected function getILSRecordId($id)
    {
        //get the ILS-specific recordId
        if ($this->ilsIdentifier != "default") {
            $ilsRecordId = $this->getRecord($id)->getILSIdentifier($this->ilsIdentifier);
            if ($ilsRecordId == '') {
                return $id;
            } else {
                if (is_array($ilsRecordId)) {
                    // use ISIL for identifying the correct ILS-identifier if array is returned
                    foreach ($ilsRecordId as $recordId) {
                        if (preg_match("/^(\(".$this->isil."\)).*$/", $recordId)) {
                            return substr($recordId, strpos($recordId, "(".$this->isil.")")+strlen("(".$this->isil.")"));
                        }
                    }
                }
                return $ilsRecordId;
            }
        }
        return $id;
    }

}