<?php
/**
 * Ajax Controller Module
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_a_controller Wiki
 */
namespace Bsz\Controller;
use \VuFind\Exception\Auth as AuthException;

/**
 * This controller handles global AJAX functionality
 *
 * @category VuFind2
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_a_controller Wiki
 */
class AjaxController extends \VuFind\Controller\AjaxController
{
   
    /**
     * Support method for getItemStatuses() -- filter suppressed locations from the
     * array of item information for a particular bib record.
     *
     * @param array $record Information on items linked to a single bib record
     *
     * @return array        Filtered version of $record
     */
    protected function filterSuppressedLocations($record)
    {
        static $hideHoldings = false;
        if ($hideHoldings === false) {
            $logic = $this->serviceLocator->get('VuFind\ILSHoldLogic');
            $hideHoldings = $logic->getSuppressedLocations();
        }
        $hideHoldings[] = '';

        
        $filtered = [];
        foreach ($record as $current) {
            if (isset($current['location']) && !in_array($current['location'], $hideHoldings)) {
                $filtered[] = $current;
            }
        }
        return $filtered;
    }

    /**
     * Get Item Statuses
     *
     * This is responsible for printing the holdings information for a
     * collection of records in JSON format.
     *
     * @return \Zend\Http\Response
     * @author Chris Delis <cedelis@uillinois.edu>
     * @author Tuan Nguyen <tuan@yorku.ca>
     */
//    protected function getItemStatusesAjax()
//    {
//        $this->disableSessionWrites();  // avoid session write timing bug
//        $catalog = $this->getILS();
//        $ids = $this->params()->fromQuery('id');
//
//        $results = $catalog->getStatuses($ids);
//        
//        if (!is_array($results)) {
//            // If getStatuses returned garbage, let's turn it into an empty array
//            // to avoid triggering a notice in the foreach loop below.
//            $results = [];
//        }
//                    
//        // In order to detect IDs missing from the status response, create an
//        // array with a key for every requested ID.  We will clear keys as we
//        // encounter IDs in the response -- anything left will be problems that
//        // need special handling.
//        $missingIds = array_flip($ids);
//
//        // Get access to PHP template renderer for partials:
//        $renderer = $this->getViewRenderer();
//
//        // Load messages for response:       
//        $messages = [
//            'available' => $renderer->render('ajax/status-available.phtml'),
//            'unavailable' => $renderer->render('ajax/status-unavailable.phtml'),
//            'unknown' => $renderer->render('ajax/status-unknown.phtml')
//        ];
//
//        // Load callnumber and location settings:
//        $config = $this->getConfig();
//        $callnumberSetting = isset($config->Item_Status->multiple_call_nos)
//            ? $config->Item_Status->multiple_call_nos : 'msg';
//        $locationSetting = isset($config->Item_Status->multiple_locations)
//            ? $config->Item_Status->multiple_locations : 'msg';
//        $showFullStatus = isset($config->Item_Status->show_full_status)
//            ? $config->Item_Status->show_full_status : false;
//
//        // Loop through all the status information that came back
//        $statuses = [];
//        foreach ($results as $recordNumber => $record) {
//            // Filter out suppressed locations:
//            $record = $this->filterSuppressedLocations($record);
//
//            // Skip empty records:
//            if (count($record)) {
//                if ($locationSetting == "group") {
//                    $current = $this->getItemStatusGroup(
//                        $record, $messages, $callnumberSetting
//                    );
//                } else {
//                    $current = $this->getItemStatus(
//                        $record, $messages, $locationSetting, $callnumberSetting
//                    );
//                }
//                // If a full status display has been requested, append the HTML:
//                if ($showFullStatus) {
//                    $current['full_status'] = $renderer->render(
//                        'ajax/status-full.phtml', ['statusItems' => $record]
//                    );
//                }
//                $current['record_number'] = array_search($current['id'], $ids);
//                $statuses[] = $current;
//
//                // The current ID is not missing -- remove it from the missing list.
//                unset($missingIds[$current['id']]);
//            }
//        }
//
//        // If any IDs were missing, send back appropriate dummy data
//        foreach ($missingIds as $missingId => $recordNumber) {
//            $statuses[] = [
//                'id'                   => $missingId,
//                'availability'         => 'false',
//                'availability_message' => $messages['unknown'],
//                'location'             => $this->translate('Unknown'),
//                'locationList'         => false,
//                'reserve'              => 'false',
//                'reserve_message'      => $this->translate('Not On Reserve'),
//                'callnumber'           => '',
//                'missing_data'         => true,
//                'record_number'        => $recordNumber
//            ];
//        }
//
//        // Done
//        return $this->output($statuses, self::STATUS_OK);
//    }
//
//    /**
//     * Support method for getItemStatuses() -- process a single bibliographic record
//     * for location settings other than "group".
//     *
//     * @param array  $record            Information on items linked to a single bib
//     *                                  record
//     * @param array  $messages          Custom status HTML
//     *                                  (keys = available/unavailable)
//     * @param string $locationSetting   The location mode setting used for
//     *                                  pickValue()
//     * @param string $callnumberSetting The callnumber mode setting used for
//     *                                  pickValue()
//     *
//     * @return array                    Summarized availability information
//     */
//    protected function getItemStatus($record, $messages, $locationSetting,
//        $callnumberSetting
//    ) {
//        // Summarize call number, location and availability info across all items:
//        $callNumbers = $locations = [];
//        $use_unknown_status = $available = false;
//        foreach ($record as $info) {
//            // Find an available copy
//            if ($info['availability']) {
//                $available = true;
//            }
//            // Check for a use_unknown_message flag
//            if (isset($info['use_unknown_message'])
//                && $info['use_unknown_message'] == true
//            ) {
//                $use_unknown_status = true;
//            }
//            // Store call number/location info:
//            $callNumbers[] = $info['callnumber'];
//            $locations[] = $info['location'];
//        }
//
//        // Determine call number string based on findings:
//        $callNumber = $this->pickValue(
//            $callNumbers, $callnumberSetting, 'Multiple Call Numbers'
//        );
//
//        // Determine location string based on findings:
//        $location = $this->pickValue(
//            $locations, $locationSetting, 'Multiple Locations', 'location_'
//        );
//
//        $availability_message = $use_unknown_status
//            ? $messages['unknown']
//            : $messages[$available ? 'available' : 'unavailable'];
//
//        // Send back the collected details:
//        return [
//            'id' => $record[0]['id'],
//            'availability' => ($available ? 'true' : 'false'),
//            'availability_message' => $availability_message,
//            'count' => count($record),
//            'location' => htmlentities($location, ENT_COMPAT, 'UTF-8'),
//            'locationList' => false,
//            'reserve' =>
//                ($record[0]['reserve'] == 'Y' ? 'true' : 'false'),
//            'reserve_message' => $record[0]['reserve'] == 'Y'
//                ? $this->translate('on_reserve')
//                : $this->translate('Not On Reserve'),
//            'callnumber' => htmlentities($callNumber, ENT_COMPAT, 'UTF-8')
//        ];
//    }
    
    protected function dedupCheckboxAjax() {
        $status = $this->params()->fromPost('status');
        $status = $status == 'true' ? true : false;
        $dedup = $this->serviceLocator->get('Bsz/Config/Dedup');
        $dedup->store(['group' => $status]); 
        return $this->output([], self::STATUS_OK);
    }
}
