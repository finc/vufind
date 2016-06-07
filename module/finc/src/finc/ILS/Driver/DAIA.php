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
use VuFind\Exception\ILS as ILSException;

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
     * Flag to switch on/off caching for DAIA items
     *
     * @var bool
     */
    protected $daiaCacheEnabled = false;

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
        if (isset($this->config['DAIA']['daiaCache'])) {
            $this->daiaCacheEnabled = $this->config['DAIA']['daiaCache'];
        } else {
            $this->debug('Caching not enabled, disabling it by default.');
        }
        if (isset($this->config['DAIA']['daiaCacheLifetime'])) {
            $this->cacheLifetime = $this->config['DAIA']['daiaCacheLifetime'];
        } else {
            $this->debug('Cache lifetime not set, using VuFind\ILS\Driver\AbstractBase default value.');
        }
    }

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatus($id)
    {
        // check ids for existing availability data in cache and skip these ids
        if ($this->daiaCacheEnabled && $item = $this->getCachedData($this->generateURI($id))) {
            if ($item != null) {
                return $item;
            }
        }

        // let's retrieve the DAIA document by URI
        try {
            $rawResult = $this->doHTTPRequest($this->generateURI($id));
            // extract the DAIA document for the current id from the
            // HTTPRequest's result
            $doc = $this->extractDaiaDoc($id, $rawResult);
            if (!is_null($doc)) {
                // parse the extracted DAIA document and return the status info
                $data = $this->parseDaiaDoc($id, $doc);
                // cache the status information
                $this->putCachedData($this->generateURI($id), $data);
                return $data;
            }
        } catch (ILSException $e) {
            $this->debug($e->getMessage());
        }

        return [];
    }

    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     * As the DAIA Query API supports querying multiple ids simultaneously
     * (all ids divided by "|") getStatuses(ids) would call getStatus(id) only
     * once, id containing the list of ids to be retrieved. This would cause some
     * trouble as the list of ids does not necessarily correspond to the VuFind
     * Record-id. Therefore getStatuses(ids) has its own logic for multiQuery-support
     * and performs the HTTPRequest itself, retrieving one DAIA response for all ids
     * and uses helper functions to split this one response into documents
     * corresponding to the queried ids.
     *
     * @param array $ids The array of record ids to retrieve the status for
     *
     * @return array    An array of status information values on success.
     */
    public function getStatuses($ids)
    {
        $status = [];

        // check cache for given ids and skip these ids if availability data is found
        foreach ($ids as $key=>$id) {
            if ($this->daiaCacheEnabled && $item = $this->getCachedData($this->generateURI($id))) {
                if ($item != null) {
                    $status[] = $item;
                    unset($ids[$key]);
                }
            }
        }

        // only query DAIA service if we have some ids left
        if (count($ids) > 0) {
            try {
                if ($this->multiQuery) {
                    // perform one DAIA query with multiple URIs
                    $rawResult = $this
                        ->doHTTPRequest($this->generateMultiURIs($ids));
                    // the id used in VuFind can differ from the document-URI
                    // (depending on how the URI is generated)
                    foreach ($ids as $id) {
                        // it is assumed that each DAIA document has a unique URI,
                        // so get the document with the corresponding id
                        $doc = $this->extractDaiaDoc($id, $rawResult);
                        if (!is_null($doc)) {
                            // a document with the corresponding id exists, which
                            // means we got status information for that record
                            $data = $this->parseDaiaDoc($id, $doc);
                            // cache the status information
                            $this->putCachedData($this->generateURI($id), $data);
                            $status[] = $data;
                        }
                        unset($doc);
                    }
                } else {
                    // multiQuery is not supported, so retrieve DAIA documents one by
                    // one
                    foreach ($ids as $id) {
                        $rawResult = $this->doHTTPRequest($this->generateURI($id));
                        // extract the DAIA document for the current id from the
                        // HTTPRequest's result
                        $doc = $this->extractDaiaDoc($id, $rawResult);
                        if (!is_null($doc)) {
                            // parse the extracted DAIA document and save the status
                            // info
                            $data = $this->parseDaiaDoc($id, $doc);
                            // cache the status information
                            $this->putCachedData($this->generateURI($id), $data);
                            $status[] = $data;
                        }
                    }
                }
            } catch (ILSException $e) {
                $this->debug($e->getMessage());
            }
        }
        return $status;
    }

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
                // custom DAIA field
                $result_item['doc_id'] = $doc_id;
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
                $result_item['location'] = $this->getItemDepartment($item);
                // get location id
                $result_item['locationid'] = $this->getItemDepartmentId($item);
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
                    if ($available['service'] == 'loan'
                        && isset($available['service']['href'])
                    ) {
                        // save the link to the ils if we have a href for loan
                        // service
                        $availableLink = $available['service']['href'];
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

        /*'returnDate' => '', // false if not recently returned(?)*/

        if (!empty($availableLink)) {
            $return['ilslink'] = $availableLink;
        }

        $return['item_notes']      = $item_notes;
        $return['status']          = $this->getStatusString($item);
        $return['availability']    = $availability;
        $return['duedate']         = $duedate;
        $return['requests_placed'] = $queue;
        $return['services']        = $this->getAvailableItemServices($services);

        // In this DAIA driver implementation addLink and is_holdable are assumed
        // Boolean as patron based availability requires either a patron-id or -type.
        // This should be handled in a custom DAIA driver
        $return['addLink'] = $return['is_holdable'] = $this->checkIsRecallable($item);
        $return['holdtype']        = $this->getHoldType($item);

        // Check if we the item is available for storage retrieval request if it is
        // not holdable.
        $return['addStorageRetrievalRequestLink'] = !$return['is_holdable']
            ? $this->checkIsStorageRetrievalRequest($item) : false;

        // add a custom Field to allow passing custom DAIA data to the frontend in
        // order to use it for more precise display of availability
        $return['customData']      = $this->getCustomData($item);

        $return['limitation_types'] = $item_limitation_types;
        
        return $return;
    }

    /**
     * Helper function to allow custom data in status array.
     *
     * @param $item
     * @return array
     */
    protected function getCustomData($item)
    {
        return [];
    }

    /**
     * Helper function to return an appropriate status string for current item.
     *
     * @param $item
     * @return string
     */
    protected function getStatusString($item)
    {
        // status cannot be null as this will crash the translator
        return '';
    }

    /**
     * Helper function to determine if item is recallable.
     * DAIA does not genuinly allow distinguishing between holdable and recallable
     * items. This could be achieved by usage of limitations but this would not be
     * shared functionality between different DAIA implementations (thus should be
     * implemented in custom drivers). Therefore this returns whether an item
     * is recallable based on unavailable services and the existence of an href.
     *
     * @param $item
     * @return bool
     */
    protected function checkIsRecallable($item)
    {
        // This basic implementation checks the item for being unavailable for loan
        // and presentation but with an existing href (as a flag for further action).
        $services = ['available'=>[], 'unavailable'=>[]];
        $href = false;
        if (isset($item['available'])) {
            // check if item is loanable or presentation
            foreach ($item['available'] as $available) {
                if (isset($available['service'])
                    && in_array($available['service'], ['loan', 'presentation'])
                ) {
                    $services['available'][] = $available['service'];
                }
            }
        }

        if (isset($item['unavailable'])) {
            foreach ($item['unavailable'] as $unavailable) {
                if (isset($unavailable['service'])
                    && in_array($unavailable['service'], ['loan', 'presentation'])
                ) {
                    $services['unavailable'][] = $unavailable['service'];
                    // attribute href is used to determine whether item is recallable
                    // or not
                    $href = isset($unavailable['href']) ? true : $href;
                }
            }
        }

        // Check if we have at least one service unavailable and a href field is set
        // (either as flag or as actual value for the next action).
        return ($href && count(
                array_diff($services['unavailable'], $services['available'])
        ));
    }

    /**
     * Helper function to determine if the item is available as storage retrieval.
     *
     * @param $item
     * @return bool
     */
    protected function checkIsStorageRetrievalRequest($item)
    {
        // This basic implementation checks the item for being available for loan
        // and presentation but with an existing href (as a flag for further action).
        $services = ['available'=>[], 'unavailable'=>[]];
        $href = false;
        if (isset($item['available'])) {
            // check if item is loanable or presentation
            foreach ($item['available'] as $available) {
                if (isset($available['service'])
                    && in_array($available['service'], ['loan', 'presentation'])
                ) {
                    $services['available'][] = $available['service'];
                    // attribute href is used to determine whether item is
                    // requestable or not
                    $href = isset($available['href']) ? true : $href;
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
            }
        }

        // Check if we have at least one service unavailable and a href field is set
        // (either as flag or as actual value for the next action).
        return ($href && count(
                array_diff($services['available'], $services['unavailable'])
        ));
    }

    /**
     * Helper function to determine the holdtype availble for current item.
     * DAIA does not genuinly allow distinguishing between holdable and recallable
     * items. This could be achieved by usage of limitations but this would not be
     * shared functionality between different DAIA implementations (thus should be
     * implemented in custom drivers). Therefore getHoldType always returns recall.
     *
     * @param $item
     * @return string 'recall'|null
     */
    protected function getHoldType($item)
    {
        // return holdtype (hold, recall or block if patron is not allowed) for item
        return $this->checkIsRecallable($item) ? 'recall' : null;
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
    protected function getItemDepartment($item)
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
    protected function getItemDepartmentId($item)
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

    /**
     * Add instance-specific context to a cache key suffix (to ensure that
     * multiple drivers don't accidentally share values in the cache).
     *
     * @param string $key Cache key suffix
     *
     * @return string
     */
    protected function formatCacheKey($key)
    {
        // Override the base class formatting with DAIA-specific URI
        // to ensure proper caching in a MultiBackend environment.
        return 'DAIA-' . md5($this->generateURI(($key)));
    }

    /**
     * Helper function for storing cached data.
     * Data is cached for up to $this->cacheLifetime seconds so that it would be
     * faster to process e.g. requests where multiple calls to the backend are made.
     *
     * @param string $key   Cache entry key
     *
     * @return void
     */
    protected function removeCachedData($key)
    {
        // Don't write to cache if we don't have a cache!
        if (null === $this->cache) {
            return;
        }
        $this->cache->removeItem($this->formatCacheKey($key));
    }
}
