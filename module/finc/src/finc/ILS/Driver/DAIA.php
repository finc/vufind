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
    protected function getItemStorage($item)
    {
        $storage = '';

        if (isset($item['storage'])
            && isset($item['storage']['content'])
        ) {
            $storage .= (empty($storage)
                ? $item['storage']['content']
                : ' - ' . $item['storage']['content']);
        }

        return (empty($storage) ? 'Unknown' : $storage);
    }
}
