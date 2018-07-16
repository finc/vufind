<?php

/**
 * OpenURL view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova  
 *  University 2010.
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
 * @package  View_Helpers
 * @author   Demian Katz <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace BszTheme\View\Helper\Bodensee;

use \VuFind\View\Helper\Root\Context;
use VuFind\Resolver\Driver\PluginManager;

/**
 * OpenURL view helper
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class OpenUrl extends \VuFind\View\Helper\Root\OpenUrl
{
    protected $params;
    protected $isil;
    
    /**
     * Constructor
     *
     * @param \VuFind\View\Helper\Root\Context $context      Context helper
     * @param array                            $openUrlRules VuFind OpenURL rules
     * @param \Zend\Config\Config              $config       VuFind OpenURL config
     * @param string                           $isil         ISIL, if possible
     */
    public function __construct(Context $context,
        $openUrlRules, PluginManager $pluginManager, $config = null, $isil = null
    ) {
        $this->context = $context;
        $this->openUrlRules = $openUrlRules;
        $this->config = $config;
        $this->resolverPluginManager = $pluginManager;
        $this->isil = $isil;
    }
    
    /**
     * Render appropriate UI controls for an OpenURL link.
     *
     * @param \VuFind\RecordDriver $driver The current recorddriver
     * @param string               $area   OpenURL context ('results', 'record'
     *  or 'holdings'
     *
     * @return object
     */
    public function __invoke($driver, $area)
    {
        $this->recordDriver = $driver;
        $this->area = $area;
        $this->params = $this->recordDriver->getOpenUrl();
        return $this;
    }

     /**
     * Public method to render the OpenURL template
     *
     * @param bool $imagebased Indicates if an image based link
     * should be displayed or not (null for system default)
     *
     * @return string
     */
    public function renderTemplate($imagebased = null)
    {
        if (null !== $this->config && isset($this->config->url)) {
            // Trim off any parameters (for legacy compatibility -- default config
            // used to include extraneous parameters):
            list($base) = explode('?', $this->config->url);
        } else {
            $base = false;
        }

        $embed = (isset($this->config->embed) && !empty($this->config->embed));

        $embedAutoLoad = isset($this->config->embed_auto_load)
            ? $this->config->embed_auto_load : false;
        // ini values 'true'/'false' are provided via ini reader as 1/0
        // only check embedAutoLoad for area if the current area passed checkContext
        if (!($embedAutoLoad === "1" || $embedAutoLoad === "0")
            && !empty($this->area)
        ) {
            // embedAutoLoad is neither true nor false, so check if it contains an
            // area string defining where exactly to use autoloading
            $embedAutoLoad = in_array(
                strtolower($this->area),
                array_map(
                    'trim',
                    array_map(
                        'strtolower',
                        explode(',', $embedAutoLoad)
                    )
                )
            );
        }

        // instantiate the resolver plugin to get a proper resolver link
        $resolver = isset($this->config->resolver)
            ? $this->config->resolver : 'other';
        $openurl = $this->recordDriver->getOpenUrl();
        if ($this->resolverPluginManager->has($resolver)) {
            $resolverObj = new \VuFind\Resolver\Connection(
                $this->resolverPluginManager->get($resolver)
            );
            $resolverUrl = $resolverObj->getResolverUrl($openurl);
        } else {
            $resolverUrl = empty($base) ? '' : $base . '?' . $openurl;
        }
        // Build parameters needed to display the control:
        $params = [
            'resolverUrl' => $resolverUrl,
            'openUrl' => $openurl,
            'openUrlBase' => empty($base) ? false : $base,
            'openUrlWindow' => empty($this->config->window_settings)
                ? false : $this->config->window_settings,
            'openUrlGraphic' => empty($this->config->graphic)
                ? false : $this->config->graphic,
            'openUrlGraphicWidth' => empty($this->config->graphic_width)
                ? false : $this->config->graphic_width,
            'openUrlGraphicHeight' => empty($this->config->graphic_height)
                ? false : $this->config->graphic_height,
            'openUrlEmbed' => $embed,
            'openUrlEmbedAutoLoad' => $embedAutoLoad
        ];
        $this->addImageBasedParams($imagebased, $params);

        // Render the subtemplate:
        return $this->context->__invoke($this->getView())->renderInContext(
            'Helpers/openurl.phtml', $params
        );
    }
    
        /**
     * Support method for renderTemplate() -- process image based parameters.
     *
     * @param bool  $imagebased Indicates if an image based link
     * should be displayed or not (null for system default)
     * @param array $params     OpenUrl parameters set so far
     *
     * @return void
     */
    protected function addImageBasedParams($imagebased, & $params)
    {
        $params['openUrlImageBasedMode'] = $this->getImageBasedLinkingMode();
        $params['openUrlImageBasedSrc'] = null;

        if (null === $imagebased) {
            $imagebased = $this->imageBasedLinkingIsActive();
        }

        if ($imagebased) {
            if (!isset($this->config->dynamic_graphic)) {
                // if imagebased linking is forced by the template, but it is not
                // configured properly, throw an exception
                throw new \Exception(
                    'Template tries to display OpenURL as image based link, but
                     Image based linking is not configured! Please set parameter
                     dynamic_graphic in config file.'
                );
            }

            // Check if we have an image-specific OpenURL to use to override
            // the default value when linking the image.
            $params['openUrlImageBasedOverride'] = $this->recordDriver
                ->tryMethod('getImageBasedOpenUrl');
            
            $resolver = isset($this->config->resolver)
                ? $this->config->resolver : 'other';
            
            // Concatenate image based OpenUrl base and OpenUrl
            // to a usable image reference
            $base = $this->config->dynamic_graphic;                
         
            if ($this->resolverPluginManager->has($resolver)) {
                $resolverObj = new \VuFind\Resolver\Connection(
                    $this->resolverPluginManager->get($resolver)
                );
                $imageOpenUrl = $resolverObj->getResolverImageParams($params['openUrl']);
            } else {
                $imageOpenUrl = empty($base) ? '' : $base;
            }
//            $imageOpenUrl = $params['openUrlImageBasedOverride']
//                ? $params['openUrlImageBasedOverride'] : $params['openUrl'];
            $params['openUrlImageBasedSrc'] = $base
                . ((false === strpos($base, '?')) ? '?' : '&')
                . $imageOpenUrl;
        }
        return $params;
    }
    
    /**
     * Just returns the URL, without rendering. 
     * 
     * @return string
     */
    public function getUrl($base = '', $additions)
    {
        // instantiate the resolver plugin to get a proper resolver link
        if ($this->area == 'illform') {
            $resolver = 'ill';            
        } else {
            $resolver = isset($this->config->resolver)
                ? $this->config->resolver : 'other';            
        }

        $openurl = $this->recordDriver->getOpenUrl();
        if ($this->resolverPluginManager->has($resolver)) {
            $resolverObj = new \VuFind\Resolver\Connection(
                $this->resolverPluginManager->get($resolver)
            );
            $resolverUrl = $resolverObj->getResolverUrl($openurl);
        } else {
            $resolverUrl = empty($base) ? '' : $base . '?' . $openurl;
        }
        
        $resolverUrl = $resolverUrl.'&'.http_build_query($additions);
        
        return $resolverUrl;        
    }
}
