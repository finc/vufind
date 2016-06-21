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
 * @category VuFind
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace finc\Controller;
use VuFind\Exception\Auth as AuthException;

/**
 * This controller handles global AJAX functionality
 *
 * @category VuFind
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class AjaxController extends \VuFind\Controller\AjaxController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Fetch Links from resolver given an OpenURL and format as HTML
     * and output the HTML content in JSON object.
     *
     * @return \Zend\Http\Response
     * @author Gregor Gawol <gawol@ub.uni-leipzig.de>
     * @author Graham Seaman <Graham.Seaman@rhul.ac.uk>
     */
    protected function getResolverLinksAjax()
    {
        $this->disableSessionWrites();  // avoid session write timing bug
        $openUrl = $this->params()->fromQuery('openurl', '');
        $requestedResolver = $this->params()->fromQuery('resolvertype', '');
        $searchClassId = $this->params()->fromQuery('searchClassId', '');

        $config = $this->getConfig('Resolver');
        $resolvers = explode(',', $config->General->active_resolvers);

        if (in_array($requestedResolver, $resolvers) && isset($config->$requestedResolver)) {
            $resolverType = isset($config->$requestedResolver->resolver)
                ? $config->$requestedResolver->resolver : 'other';
            $pluginManager = $this->getServiceLocator()
                ->get('VuFind\ResolverDriverPluginManager');
            if (!$pluginManager->has($resolverType)) {
                return $this->output(
                    $this->translate("Could not load driver for $resolverType"),
                    self::STATUS_ERROR,
                    500
                );
            }
            $resolver = new \VuFind\Resolver\Connection(
                $pluginManager->get($resolverType)
            );
            if (isset($config->$requestedResolver->resolver_cache)) {
                $resolver->enableCache($config->$requestedResolver->resolver_cache);
            }
            $result = $resolver->fetchLinks($openUrl);

            // Sort the returned links into categories based on service type:
            $electronic = $print = $services = [];
            foreach ($result as $link) {
                switch (isset($link['service_type']) ? $link['service_type'] : '') {
                    case 'getHolding':
                        $print[] = $link;
                        break;
                    case 'getWebService':
                        $services[] = $link;
                        break;
                    case 'getDOI':
                        // Special case -- modify DOI text for special display:
                        $link['title'] = $this->translate('Get full text');
                        $link['coverage'] = '';
                    case 'getFullTxt':
                    default:
                        $electronic[] = $link;
                        break;
                }
            }

            // Get the OpenURL base:
            if (isset($config->$requestedResolver) && isset($config->$requestedResolver->url)) {
                // Trim off any parameters (for legacy compatibility -- default config
                // used to include extraneous parameters):
                list($base) = explode('?', $config->$requestedResolver->url);
            } else {
                $base = false;
            }

            // Render the links using the view:
            $view = [
                'openUrlBase' => $base, 'openUrl' => $openUrl, 'print' => $print,
                'electronic' => $electronic, 'services' => $services,
                'searchClassId' => $searchClassId
            ];
        }
        $html = $this->getViewRenderer()->render('ajax/resolverLinks.phtml', $view);

        // output HTML encoded in JSON object
        return $this->output($html, self::STATUS_OK);
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
    protected function getItemStatusesAjax()
    {
        $this->disableSessionWrites();  // avoid session write timing bug
        $catalog = $this->getILS();
        $ids = $this->params()->fromPost('id', $this->params()->fromQuery('id'));

        // Call getStatuses only if the ILS is not in offline mode
        if ($catalog->getOfflineMode() === false) {
            $results = $catalog->getStatuses($ids);
            if (!is_array($results)) {
                // If getStatuses returned garbage, let's turn it into an empty array
                // to avoid triggering a notice in the foreach loop below.
                $results = [];
            }
        } else {
            $results = [];
        }

        // In order to detect IDs missing from the status response, create an
        // array with a key for every requested ID.  We will clear keys as we
        // encounter IDs in the response -- anything left will be problems that
        // need special handling.
        $missingIds = array_flip($ids);

        // Get access to PHP template renderer for partials:
        $renderer = $this->getViewRenderer();

        // Load messages for response:
        $messages = [
            'available' => $renderer->render('ajax/status-available.phtml'),
            'unavailable' => $renderer->render('ajax/status-unavailable.phtml'),
            'unknown' => $renderer->render('ajax/status-unknown.phtml')
        ];

        // Load callnumber and location settings:
        $config = $this->getConfig();
        $callnumberSetting = isset($config->Item_Status->multiple_call_nos)
            ? $config->Item_Status->multiple_call_nos : 'msg';
        $locationSetting = isset($config->Item_Status->multiple_locations)
            ? $config->Item_Status->multiple_locations : 'msg';
        $showFullStatus = isset($config->Item_Status->show_full_status)
            ? $config->Item_Status->show_full_status : false;

        // Loop through all the status information that came back
        $statuses = [];
        foreach ($results as $recordNumber => $record) {
            // Filter out suppressed locations:
            $record = $this->filterSuppressedLocations($record);

            // Skip empty records:
            if (count($record)) {
                if ($locationSetting == "group") {
                    $current = $this->getItemStatusGroup(
                        $record, $messages, $callnumberSetting
                    );
                } else {
                    $current = $this->getItemStatus(
                        $record, $messages, $locationSetting, $callnumberSetting
                    );
                }
                // If a full status display has been requested, append the HTML:
                if ($showFullStatus) {
                    $current['full_status'] = $renderer->render(
                        'ajax/status-full.phtml', ['statusItems' => $record]
                    );
                }
                $current['record_number'] = array_search($current['id'], $ids);
                $statuses[] = $current;

                // The current ID is not missing -- remove it from the missing list.
                unset($missingIds[$current['id']]);
            }
        }

        // If any IDs were missing, send back appropriate dummy data
        foreach ($missingIds as $missingId => $recordNumber) {
            $statuses[] = [
                'id'                   => $missingId,
                'availability'         => 'false',
                'availability_message' => $messages['unavailable'],
                'location'             => $this->translate('Unknown'),
                'locationList'         => false,
                'reserve'              => 'false',
                'reserve_message'      => $this->translate('Not On Reserve'),
                'callnumber'           => '',
                'missing_data'         => true,
                'record_number'        => $recordNumber
            ];
        }

        // Done
        return $this->output($statuses, self::STATUS_OK);
    }
    
    /**
     * Get additional information for display in my research area.
     *
     * This method currently only returns the items/entries count of the ILS methods
     * given as post values.
     *
     * @return \Zend\Http\Response
     */
    protected function getAdditionalAccountInfoAjax()
    {
        $this->disableSessionWrites();  // avoid session write timing bug

        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        $catalog = $this->getILS();

        // initialize the return array
        $additionalAccountInfos = [];

        // collect data for views to be counted
        $viewsToCount = $this->params()
            ->fromPost('views', $this->params()->fromQuery('views'));

        $additionalAccountInfos['countViewItems'] = $catalog->countItems(
            $viewsToCount,
            $patron
        );

        $additionalAccountInfos['countFines'] = $catalog->getFinesTotal(
            $patron
        );
        
        // Done
        return $this->output($additionalAccountInfos, self::STATUS_OK);
    }
    
    /**
     * Get Ils Status
     *
     * This will check the ILS for being online and will return the ils-offline 
     * template upon failure.
     *
     * @return \Zend\Http\Response
     * @author André Lahmann <lahmann@ub.uni-leipzig.de>
     */
    protected function getIlsStatusAjax()
    {
        $this->disableSessionWrites();  // avoid session write timing bug
        if ($this->getILS()->getOfflineMode() == 'ils-offline') {
            return $this->output(
                $this->getViewRenderer()->render('Helpers/ils-offline.phtml'),
                self::STATUS_OK
            );
        }
        return $this->output('', self::STATUS_OK);
    }

    /**
     * Support method for getItemStatuses() -- process a single bibliographic record
     * for location settings other than "group".
     *
     * @param array  $record            Information on items linked to a single bib
     *                                  record
     * @param array  $messages          Custom status HTML
     *                                  (keys = available/unavailable)
     * @param string $locationSetting   The location mode setting used for
     *                                  pickValue()
     * @param string $callnumberSetting The callnumber mode setting used for
     *                                  pickValue()
     *
     * @return array                    Summarized availability information
     */
    protected function getItemStatus($record, $messages, $locationSetting,
                                     $callnumberSetting
    ) {
        // Summarize call number, location and availability info across all items:
        $callNumbers = $locations = [];
        $use_unknown_status = $available = false;
        $services = [];

        foreach ($record as $info) {
            // Find an available copy
            if ($info['availability']) {
                $available = true;
            }
            // Check for a use_unknown_message flag
            if (isset($info['use_unknown_message'])
                && $info['use_unknown_message'] == true
            ) {
                $use_unknown_status = true;
            }
            // Store call number/location info:
            $callNumbers[] = $info['callnumber'];
            $locations[] = $info['location'];
            // Store all available services
            if (isset($info['services'])) {
                $services = array_merge($services, $info['services']);
            }
        }

        // Determine call number string based on findings:
        $callNumber = $this->pickValue(
            $callNumbers, $callnumberSetting, 'Multiple Call Numbers'
        );

        // Determine location string based on findings:
        $location = $this->pickValue(
            $locations, $locationSetting, 'Multiple Locations', 'location_'
        );

        if (!empty($services)) {
            $availability_message = $this
                ->reduceServices(
                    $services,
                    $available ? 'available' : 'unavailable'
                );
        } else {
            $availability_message = $use_unknown_status
                ? $messages['unknown']
                : $messages[$available ? 'available' : 'unavailable'];
        }

        // Send back the collected details:
        return [
            'id' => $record[0]['id'],
            'availability' => ($available ? 'true' : 'false'),
            'availability_message' => $availability_message,
            'location' => htmlentities($location, ENT_COMPAT, 'UTF-8'),
            'locationList' => false,
            'reserve' =>
                ($record[0]['reserve'] == 'Y' ? 'true' : 'false'),
            'reserve_message' => $record[0]['reserve'] == 'Y'
                ? $this->translate('on_reserve')
                : $this->translate('Not On Reserve'),
            'callnumber' => htmlentities($callNumber, ENT_COMPAT, 'UTF-8')
        ];
    }
    
    /**
     * Reduce an array of service names to a human-readable string.
     *
     * @param array $services Names of available services.
     *
     * @return string
     */
    protected function reduceServices(array $services, $availability = 'available')
    {
        // Normalize, dedup and sort available services
        $normalize = function ($in) {
            return strtolower(preg_replace('/[^A-Za-z]/', '', $in));
        };
        $services = array_map($normalize, array_unique($services));
        sort($services);

        // Do we need to deal with a preferred service?
        $config = $this->getConfig();
        $preferred = isset($config->Item_Status->preferred_service)
            ? $normalize($config->Item_Status->preferred_service) : false;
        if (false !== $preferred && in_array($preferred, $services)) {
            $services = [$preferred];
        }

        return $this->getViewRenderer()->render(
            'ajax/status-'.$availability.'-services.phtml',
            ['services' => $services]
        );
    }
}
