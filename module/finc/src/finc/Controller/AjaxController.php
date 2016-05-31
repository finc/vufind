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
        $viewsToCount = $this->params()->fromPost('views', $this->params()->fromQuery('views'));

        $additionalAccountInfos['countViewItems'] = $catalog->countItems(
            $viewsToCount,
            $patron
        );

        // Done
        return $this->output($additionalAccountInfos, self::STATUS_OK);
    }
}
