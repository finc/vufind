<?php
/**
 * Amsl API Controller
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2018.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category finc
 * @package  Controller
 * @author   Dorian Merz <merz@ub.uni-leipzig.de>
 * @author   Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace finc\Controller;

use VuFind\Controller\AbstractBase;

/**
 * Controller for the user account area.
 *
 * @category finc
 * @package  Controller
 * @author   Dorian Merz <merz@ub.uni-leipzig.de>
 * @author   Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class AmslResourceController extends AbstractBase
{
    /**
     * Amsl.ini configuration.
     *
     * @var $config
     * @access protected
     */
    protected $config = [];

    /**
     * HTTP client
     *
     * @var \Zend\Http\Client
     * @access protected
     */
    protected $httpClient;


    /**
     * Constructor
     *
     * @param \Zend\Config\Config   $config     VuFind configuration
     * @param \VuFind\Http          $httpClient HttpClient
    */
    public function __construct(
        \Zend\Config\Config $config,
        \VuFindHttp\HttpService $httpClient
    ) {
        $this->config = $config;
        $this->httpClient = $httpClient;
    }

    /**
     * Amsl action - controller method
     *
     * @return \Zend\View\Model\ViewModel
     * @throws \Exception
     * @access public
     */
    public function homeAction()
    {
        // Make view
        $api_conf = $this->config->get('API');
        $view = $this->createViewModel();
        try {
            if (
                null == (
                $result
                    = $this->httpClient->get($api_conf->url)
                )
            ) {
                throw new \Exception(
                    'Unexpected value: No api result received'
                );
            }
            if ($result->isSuccess()) {
                switch ($api_conf->response_type) {
                case 'application/json':
                    $amsl_sources = json_decode($result->getBody(), true);
                    break;
                default:
                    throw new \Exception(
                        'Invalid argument: No valid header scheme defined'
                    );
                    break;
                }
            }
            if (isset($amsl_sources)) {
                $view->sources = $this->createSourceHierarchy($amsl_sources);
            }
        }
        catch (\Exception $e) {
            $this->flashMessenger()->addMessage(
                'resources_cannot_received',
                'error'
            );
        }
        $view->setTemplate('amsl/sources-list');
        return $view;
    }

    /**
     * Sorts the input array according to the values for main_key and sub_key
     *
     * @param array $amsl_sources
     *
     * @return array $out
     * @access protected
     */
    protected function createSourceHierarchy(array $amsl_sources)
    {
        $struct = $this->config->get('Mapping');
        $main_key = $struct->main_key;
        $sub_key = $struct->sub_key;
        $sources = [];

        foreach ($amsl_sources as $source) {
            if (isset($source[$main_key])) {
                if (isset($source[$sub_key])){
                    $label = $this->renderLabel($struct->sub_label, $source);
                    $sources[$source[$main_key]][$label] = $source;
                } else {
                    $sources[$source[$main_key]][$struct->default_sub_label]
                        = $source;
                }
            } else {
                if (isset($source[$sub_key])){
                    $label = $this->renderLabel($struct->sub_label, $source);
                    $default[$label] = $source;
                } else {
                    $default[$struct->default_sub_label] = $source;
                }
            }
        }
        ksort($sources);
        $out = [];
        foreach ($sources as $main) {
            $label = $this->renderLabel($struct->main_label, current($main));
            $out[$label] = $main;
        }
        if (isset($default)) $out[$struct->default_main_label] = $default;
        return $out;
    }

    /**
     * Helper funtion to render label
     *
     * @param $pattern
     * @param $input_array
     *
     * @return mixed
     * @access protected
     */
    protected function renderLabel($pattern, $input_array) {
        $struct = [];
        $replace = [];
        preg_match_all('/\%\%(\w+)\%\%/', $pattern, $struct);
        foreach ($struct[1] as $key) {
            $replace[] = (isset($input_array[$key])) ? $input_array[$key] : '';
        }
        return str_replace(
            $struct[0],
            $replace,
            $pattern
        );
    }
}
