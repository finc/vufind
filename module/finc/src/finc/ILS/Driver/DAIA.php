<?php
/**
 * ILS Driver for VuFind to query availability information via DAIA.
 *
 * Based on the proof-of-concept-driver by Till Kinstler, GBV.
 * Relaunch of the daia driver developed by Oliver Goldschmidt.
 *
 * PHP version 5
 *
 * Copyright (C) Jochen Lienhard 2014.
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
 * @author   Jochen Lienhard <lienhard@ub.uni-freiburg.de>
 * @author   Oliver Goldschmidt <o.goldschmidt@tu-harburg.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace finc\ILS\Driver;

/**
 * ILS Driver for VuFind to query availability information via DAIA.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Jochen Lienhard <lienhard@ub.uni-freiburg.de>
 * @author   Oliver Goldschmidt <o.goldschmidt@tu-harburg.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class DAIA extends \VuFind\ILS\Driver\DAIA
{

    /**
     * Parse an array with DAIA status information.
     *
     * @param string $id        Record id for the DAIA array.
     * @param array  $daiaArray Array with raw DAIA status information.
     *
     * @return array            Array with VuFind compatible status information.
     */
    protected function parseDaiaArray($id, $daiaArray)
    {
        $doc_id = null;
        $doc_href = null;
        if (isset($daiaArray['id'])) {
            $doc_id = $daiaArray['id'];
        }
        if (isset($daiaArray['href'])) {
            // url of the document (not needed for VuFind)
            $doc_href = $daiaArray['href'];
        }
        if (isset($daiaArray['message'])) {
            // log messages for debugging
            $this->logMessages($daiaArray['message'], 'document');
        }
        // if one or more items exist, iterate and build result-item
        if (isset($daiaArray['item']) && is_array($daiaArray['item'])) {
            $number = 0;
            foreach ($daiaArray['item'] as $item) {
                $result_item = [];
                $result_item['id'] = $id;
                $result_item['item_id'] = $item['id'];
                // custom DAIA field used in getHoldLink()
                $result_item['ilslink']
                    = (isset($item['href']) ? $item['href'] : $doc_href);
                // count items
                $number++;
                $result_item['number'] = $this->getItemNumber($item, $number);
                // set default value for barcode
                $result_item['barcode'] = $this->getItemBarcode($item);
                // set default value for reserve
                $result_item['reserve'] = $this->getItemReserveStatus($item);
                // get callnumber
                $result_item['callnumber'] = $this->getItemCallnumber($item);
                // get location
                $result_item['location'] = $this->getItemLocation($item);
                // get location id
                $result_item['locationid'] = $this->getItemLocationId($item);
                // get location link
                $result_item['locationhref'] = $this->getItemLocationLink($item);
                // get location
                $result_item['storage'] = $this->getItemStorage($item);
                // status and availability will be calculated in own function
                $result_item = $this->getItemStatus($item) + $result_item;
                // add result_item to the result array
                $result[] = $result_item;
            } // end iteration on item
        }

        return $result;
    }

    /**
     * Returns an array with status information for provided item.
     *
     * @param array $item Array with DAIA item data
     *
     * @return array
     */
    protected function getItemStatus($item)
    {
        $availability = false;
        $status = ''; // status cannot be null as this will crash the translator
        $duedate = null;
        $availableLink = '';
        $queue = '';
        $is_holdable = false;
        $item_notes = [];
        $item_limitation_types = [];
        $services = [];

        if (isset($item['available'])) {
            // check if item is loanable or presentation
            foreach ($item['available'] as $available) {
                if (isset($available['service'])
                    && in_array($available['service'], ['loan', 'presentation'])
                ) {
                    $services['available'][] = $available['service'];
                }
                // attribute service can be set once or not
                if (isset($available['service'])
                    && in_array(
                        $available['service'],
                        ['loan', 'presentation', 'openaccess']
                    )
                ) {
                    // set item available if service is loan, presentation or
                    // openaccess
                    $availability = true;
                    if ($available['service'] == 'loan') {
                        if (isset($available['service']['href'])) {
                            // save the link to the ils if we have a href for loan
                            // service
                            $availableLink = $available['service']['href'];
                        }
                        $is_holdable = true;
                    }
                }

                // use limitation element for status string
                if (isset($available['limitation'])) {
                    $item_notes = array_merge(
                        $item_notes,
                        $this->getItemLimitationContent($available['limitation'])
                    );
                    $item_limitation_types = array_merge(
                        $item_limitation_types,
                        $this->getItemLimitationTypes($available['limitation'])
                    );
                }

                // log messages for debugging
                if (isset($available['message'])) {
                    $this->logMessages($available['message'], 'item->available');
                }
            }
        }

        if (isset($item['unavailable'])) {
            foreach ($item['unavailable'] as $unavailable) {
                if (isset($unavailable['service'])
                    && in_array($unavailable['service'], ['loan', 'presentation'])
                ) {
                    $services['unavailable'][] = $unavailable['service'];
                }
                // attribute service can be set once or not
                if (isset($unavailable['service'])
                    && in_array(
                        $unavailable['service'],
                        ['loan', 'presentation', 'openaccess']
                    )
                ) {
                    if ($unavailable['service'] == 'loan'
                        && isset($unavailable['service']['href'])
                    ) {
                        //save the link to the ils if we have a href for loan service
                    }

                    // use limitation element for status string
                    if (isset($unavailable['limitation'])) {
                        $item_notes = array_merge(
                            $item_notes,
                            $this->getItemLimitationContent($unavailable['limitation'])
                        );
                        $item_limitation_types = array_merge(
                            $item_limitation_types,
                            $this->getItemLimitationTypes($unavailable['limitation'])
                        );
                    }
                }
                // attribute expected is mandatory for unavailable element
                if (isset($unavailable['expected'])) {
                    try {
                        $duedate = $this->dateConverter
                            ->convertToDisplayDate(
                                'Y-m-d', $unavailable['expected']
                            );
                    } catch (\Exception $e) {
                        $this->debug('Date conversion failed: ' . $e->getMessage());
                        $duedate = null;
                    }
                }

                // attribute queue can be set
                if (isset($unavailable['queue'])) {
                    $queue = $unavailable['queue'];
                }

                // log messages for debugging
                if (isset($unavailable['message'])) {
                    $this->logMessages($unavailable['message'], 'item->unavailable');
                }
            }
        }

        /*'availability' => '0',
        'status' => '',  // string - needs to be computed from availability info
        'duedate' => '', // if checked_out else null
        'returnDate' => '', // false if not recently returned(?)
        'requests_placed' => '', // total number of placed holds
        'is_holdable' => false, // place holding possible?*/

        if (!empty($availableLink)) {
            $return['ilslink'] = $availableLink;
        }

        $return['is_holdable']     = $is_holdable;
        $return['item_notes']      = $item_notes;
        $return['status']          = $status;
        $return['availability']    = $availability;
        $return['duedate']         = $duedate;
        $return['requests_placed'] = $queue;
        $return['limitation_types'] = $item_limitation_types;
        $return['services']        = $this->getAvailableItemServices($services);

        return $return;
    }

    /**
     * Returns the evaluated value of the provided limitation element
     *
     * @param array $limitations Array with DAIA limitation data
     *
     * @return array
     */
    protected function getItemLimitation($limitations)
    {
        $itemLimitation = [];
        foreach ($limitations as $limitation) {
            // return the first limitation with content set
            if (isset($limitation['content'])) {
                $itemLimitation[] = $limitation['content'];
            }
        }
        return $itemLimitation;
    }

    /**
     * Returns the value for "location" in VuFind getStatus/getHolding array
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemLocation($item)
    {
        return isset($item['department']) && isset($item['department']['content'])
            && !empty($item['department']['content'])
                ? $item['department']['content']
                : 'Unknown';
    }

    /**
     * Returns the value for "location" id in VuFind getStatus/getHolding array
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemLocationId($item)
    {
        return isset($item['department']) && isset($item['department']['id'])
            ? $item['department']['id'] : '';
    }

    /**
     * Returns the value for "location" in VuFind getStatus/getHolding array
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemStorage($item)
    {
        return isset($item['storage']) && isset($item['storage']['content'])
            && !empty($item['storage']['content'])
                ? $item['storage']['content']
                : 'Unknown';
    }

    /**
     * Returns the value for "location" id in VuFind getStatus/getHolding array
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemStorageId($item)
    {
        return isset($item['storage']) && isset($item['storage']['id']) 
            ? $item['storage']['id'] : '';
    }

    /**
     * Returns the evaluated values of the provided limitations element
     *
     * @param array $limitations Array with DAIA limitation data
     *
     * @return array
     */
    protected function getItemLimitationContent($limitations)
    {
        $itemLimitationContent = [];
        foreach ($limitations as $limitation) {
            // return the first limitation with content set
            if (isset($limitation['content'])) {
                $itemLimitationContent[] = $limitation['content'];
            }
        }
        return $itemLimitationContent;
    }

    /**
     * Returns the evaluated values of the provided limitations element
     *
     * @param array $limitations Array with DAIA limitation data
     *
     * @return array
     */
    protected function getItemLimitationTypes($limitations)
    {
        $itemLimitationTypes = [];
        foreach ($limitations as $limitation) {
            // return the first limitation with content set
            if (isset($limitation['id'])) {
                $itemLimitationTypes[] = $limitation['id'];
            }
        }
        return $itemLimitationTypes;
    }
}
