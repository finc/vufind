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
class FincDAIA extends DAIA implements \Zend\Log\LoggerAwareInterface
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
        if (!isset($this->config['DAIA']['baseUrl'])) {
            throw new ILSException('DAIA/baseUrl configuration needs to be set.');
        }

        $this->baseURL = $this->config['DAIA']['baseUrl'];

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

    /**
     * Get the Record-Object from the RecordDriver.
     *
     * @param string $id ID of record to retrieve
     *
     * @return \VuFind\RecordDriver\AbstractBase
     */
    public function getRecord($id)
    {
        return $this->recordLoader->load($id);
    }

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @throws ILSException
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatus($id)
    {
        $holding = $this->daiaToHolding($this->getILSRecordId($id));
        return $holding;
    }

    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     *
     * @param array $ids The array of record ids to retrieve the status for
     *
     * @throws ILSException
     * @return array     An array of getStatus() return values on success.
     */
    public function getStatuses($ids)
    {
        $items = array();
        foreach ($ids as $id) {
            $items[] = $this->getShortStatus($this->getILSRecordId($id));
        }
        return $items;
    }

    /**
     * Query a DAIA server and return the result as DOMDocument object.
     * The returned object is an XML document containing
     * content as described in the DAIA format specification.
     *
     * @param string $id Document to look up.
     *
     * @return DOMDocument Object representation of an XML document containing
     * content as described in the DAIA format specification.
     */
    protected function queryDAIA($id)
    {
        $opts = array(
            'http' => array(
                'ignore_errors' => 'true',
            )
        );

        $context = stream_context_create($opts);
        libxml_set_streams_context($context);

        $daia = new DOMDocument();
        $daia->load($this->baseURL . $id);

        return $daia;
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
        if ($this->ilsIdentifier == "default") {
            return $id;
        } else {
            $ilsRecordId = $this->getRecord($id)->getILSIdentifier($this->ilsIdentifier);
            if ($ilsRecordId == '')
            {
                return $id;
            } else {
                if (is_array($ilsRecordId)) {
                    // use ISIL for identifying the correct ILS-identifier if array is returned
                    foreach ($ilsRecordId as $recordId) {
                        if (preg_match($recordId, "/^(".$this->isil.").*$/")) {
                            return substr($recordId, strpos($recordId, "(".$this->isil.")")+strlen("(".$this->isil.")"));
                        }
                    }
                }

                return $ilsRecordId;
            }

            // DAIA Request with PPN from MarcRecord
            //$daia = $this->queryDAIA($this->getSolrRecord($id)->getFincPPN()->getData());

            // DAIA Request with PPN from Solr
            //$daia = $this->queryDAIA($this->getSolrRecord($id)->getFincPPNSolr());

            // DAIA Request with barcode
            //$daia = $this->queryDAIA($this->getSolrRecord($id)->getFincBarcode());
        }
    }

    /**
     * Flatten a DAIA response to an array of holding information.
     *
     * @param string $id Document to look up.
     *
     * @return array
     */
    protected function daiaToHolding($id)
    {
        $daia = $this->queryDAIA($id);
        // get Availability information from DAIA
        $documentlist = $daia->getElementsByTagName('document');

        // handle empty DAIA response
        if ($documentlist->length == 0 &&
            $daia->getElementsByTagName("message")->item(0)->attributes->getNamedItem("errno")->nodeValue == "404") {
            $this->debug("Error: " . $daia->getElementsByTagName("message")->item(0)->attributes->getNamedItem("errno")->nodeValue
                . " reported for DAIA request");
        }

        $status = array();
        for ($b = 0; $documentlist->item($b) !== null; $b++) {
            $itemlist = $documentlist->item($b)->getElementsByTagName('item');
            $ilslink='';
            if ($documentlist->item($b)->attributes->getNamedItem('href')!==null) {
                $ilslink = $documentlist->item($b)->attributes
                    ->getNamedItem('href')->nodeValue;
            }
            $emptyResult = array(
                'callnumber' => '-',
                'availability' => '0',
                'number' => 1,
                'reserve' => 'No',
                'duedate' => '',
                'queue'   => '',
                'delay'   => '',
                'barcode' => 'No samples',
                'status' => '',
                'id' => $id,
                'location' => '',
                'ilslink' => $ilslink,
                'label' => 'No samples'
            );
            for ($c = 0; $itemlist->item($c) !== null; $c++) {
                $result = array(
                    'callnumber' => '',
                    'availability' => '0',
                    'number' => ($c+1),
                    'reserve' => 'No',
                    'duedate' => '',
                    'queue'   => '',
                    'delay'   => '',
                    'barcode' => 1,
                    'status' => '',
                    'id' => $id,
                    'item_id' => '',
                    'recallhref' => '',
                    'location' => '',
                    'location.id' => '',
                    'location.href' => '',
                    'label' => '',
                    'notes' => array()
                );
                if ($itemlist->item($c)->attributes->getNamedItem('id') !== null) {
                    $result['item_id'] = $itemlist->item($c)->attributes
                        ->getNamedItem('id')->nodeValue;
                }
                if ($itemlist->item($c)->attributes->getNamedItem('href') !== null) {
                    $result['recallhref'] = $itemlist->item($c)->attributes
                        ->getNamedItem('href')->nodeValue;
                }
                $departmentElements = $itemlist->item($c)
                    ->getElementsByTagName('department');
                if ($departmentElements->length > 0) {
                    if ($departmentElements->item(0)->nodeValue) {
                        $result['location']
                            = $departmentElements->item(0)->nodeValue;
                        $result['location.id'] = $departmentElements
                            ->item(0)->attributes->getNamedItem('id')->nodeValue;
                        $result['location.href'] = $departmentElements
                            ->item(0)->attributes->getNamedItem('href')->nodeValue;
                    }
                }
                $storageElements
                    = $itemlist->item($c)->getElementsByTagName('storage');
                if ($storageElements->length > 0) {
                    if ($storageElements->item(0)->nodeValue) {
                        $result['location'] = $storageElements->item(0)->nodeValue;
                        //$result['location.id'] = $storageElements->item(0)
                        //  ->attributes->getNamedItem('id')->nodeValue;
                        $result['location.href'] = $storageElements->item(0)
                            ->attributes->getNamedItem('href')->nodeValue;
                        //$result['barcode'] = $result['location.id'];
                    }
                }
                $barcodeElements
                    = $itemlist->item($c)->getElementsByTagName('identifier');
                if ($barcodeElements->length > 0) {
                    if ($barcodeElements->item(0)->nodeValue) {
                        $result['barcode'] = $barcodeElements->item(0)->nodeValue;
                    }
                }
                $labelElements = $itemlist->item($c)->getElementsByTagName('label');
                if ($labelElements->length > 0) {
                    if ($labelElements->item(0)->nodeValue) {
                        $result['label'] = $labelElements->item(0)->nodeValue;
                        $result['callnumber']
                            = urldecode($labelElements->item(0)->nodeValue);
                    }
                }
                $messageElements
                    = $itemlist->item($c)->getElementsByTagName('message');
                if ($messageElements->length > 0) {
                    for ($m = 0; $messageElements->item($m) !== null; $m++) {
                        $errno = $messageElements->item($m)->attributes
                            ->getNamedItem('errno')->nodeValue;
                        if ($errno === '404') {
                            $result['status'] = 'missing';
                        } else if ($this->logger) {
                            $lang = $messageElements->item($m)->attributes
                                ->getNamedItem('lang')->nodeValue;
                            $logString = "[DAIA] message for {$lang}: "
                                . $messageElements->item($m)->nodeValue;
                            $this->debug($logString);
                        }
                    }
                }

                //$loanAvail = 0;
                //$loanExp = 0;
                //$presAvail = 0;
                //$presExp = 0;

                $unavailableElements = $itemlist->item($c)
                    ->getElementsByTagName('unavailable');
                if ($unavailableElements->item(0) !== null) {
                    for ($n = 0; $unavailableElements->item($n) !== null; $n++) {
                        $service = $unavailableElements->item($n)->attributes
                            ->getNamedItem('service');
                        $expectedNode = $unavailableElements->item($n)->attributes
                            ->getNamedItem('expected');
                        $queueNode = $unavailableElements->item($n)->attributes
                            ->getNamedItem('queue');
                        if ($service !== null) {
                            $service = $service->nodeValue;
                            if ($service === 'presentation') {
                                $result['presentation.availability'] = '0';
                                $result['presentation_availability'] = '0';
                                if ($expectedNode !== null) {
                                    $result['presentation.duedate']
                                        = $expectedNode->nodeValue;
                                }
                                if ($queueNode !== null) {
                                    $result['presentation.queue']
                                        = $queueNode->nodeValue;
                                }
                                $result['availability'] = '0';
                            } elseif ($service === 'loan') {
                                $result['loan.availability'] = '0';
                                $result['loan_availability'] = '0';
                                if ($expectedNode !== null) {
                                    $result['loan.duedate'] = $expectedNode->nodeValue;
                                }
                                if ($queueNode !== null) {
                                    $result['loan.queue'] = $queueNode->nodeValue;
                                }
                                $result['availability'] = '0';
                            } elseif ($service === 'interloan') {
                                $result['interloan.availability'] = '0';
                                if ($expectedNode !== null) {
                                    $result['interloan.duedate']
                                        = $expectedNode->nodeValue;
                                }
                                if ($queueNode !== null) {
                                    $result['interloan.queue'] = $queueNode->nodeValue;
                                }
                                $result['availability'] = '0';
                            } elseif ($service === 'openaccess') {
                                $result['openaccess.availability'] = '0';
                                if ($expectedNode !== null) {
                                    $result['openaccess.duedate']
                                        = $expectedNode->nodeValue;
                                }
                                if ($queueNode !== null) {
                                    $result['openaccess.queue'] = $queueNode->nodeValue;
                                }
                                $result['availability'] = '0';
                            }
                        }
                        // TODO: message/limitation
                        if ($expectedNode !== null) {
                            $result['duedate'] = $expectedNode->nodeValue;
                        }
                        if ($queueNode !== null) {
                            $result['queue'] = $queueNode->nodeValue;
                        }
                    }
                }

                $availableElements = $itemlist->item($c)
                    ->getElementsByTagName('available');
                if ($availableElements->item(0) !== null) {
                    for ($n = 0; $availableElements->item($n) !== null; $n++) {
                        $service = $availableElements->item($n)->attributes
                            ->getNamedItem('service');
                        $delayNode = $availableElements->item($n)->attributes
                            ->getNamedItem('delay');
                        if ($service !== null) {
                            $service = $service->nodeValue;
                            if ($service === 'presentation') {
                                $result['presentation.availability'] = '1';
                                $result['presentation_availability'] = '1';
                                if ($delayNode !== null) {
                                    $result['presentation.delay']
                                        = $delayNode->nodeValue;
                                }
                                $result['availability'] = '1';
                            } elseif ($service === 'loan') {
                                $result['loan.availability'] = '1';
                                $result['loan_availability'] = '1';
                                if ($delayNode !== null) {
                                    $result['loan.delay'] = $delayNode->nodeValue;
                                }
                                $result['availability'] = '1';
                            } elseif ($service === 'interloan') {
                                $result['interloan.availability'] = '1';
                                if ($delayNode !== null) {
                                    $result['interloan.delay'] = $delayNode->nodeValue;
                                }
                                $result['availability'] = '1';
                            } elseif ($service === 'openaccess') {
                                $result['openaccess.availability'] = '1';
                                if ($delayNode !== null) {
                                    $result['openaccess.delay'] = $delayNode->nodeValue;
                                }
                                $result['availability'] = '1';
                            }
                        }
                        // TODO: message/limitation
                        if ($delayNode !== null) {
                            $result['delay'] = $delayNode->nodeValue;
                        }
                    }
                }
                // document has no availability elements, so set availability
                // and barcode to -1
                if ($availableElements->item(0) === null
                    && $unavailableElements->item(0) === null
                ) {
                    $result['availability'] = '-1';
                    $result['barcode'] = '-1';
                }
                $result['ilslink'] = $ilslink;
                $status[] = $result;
                /* $status = "available";
                if (loanAvail) return 0;
                if (presAvail) {
                    if (loanExp) return 1;
                    return 2;
                }
                if (loanExp) return 3;
                if (presExp) return 4;
                return 5;
                */
            }
            if (count($status) === 0) {
                $status[] = $emptyResult;
            }
        }
        return $status;
    }

}