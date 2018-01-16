<?php

/**
 * Factory for the default SOLR backend.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2013.
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
 * @package  Search
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Bsz\Search\Factory;

use VuFindSearch\Backend\Solr\Response\Json\RecordCollectionFactory;
use VuFindSearch\Backend\Solr\Connector;
use VuFindSearch\Backend\Solr\Backend;


class SolrDefaultBackendFactory extends \VuFind\Search\Factory\SolrDefaultBackendFactory
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->searchConfig = 'searches';
        $this->searchYaml = 'searchspecs.yaml';
        $this->facetConfig = 'facets';
    }
    
     /**
     * Get the Solr URL.
     *
     * @return string|array
     */
//    protected function getSolrUrl()
//    {
//        $url = $this->config->get('config')->Index->url;
//        $core = $this->getSolrCore();
//        
//        $router = $this->serviceLocator->get('router');
//        $request = $this->serviceLocator->get('request');
//        
//        $routeMatch = $router->match($request);
//        $route = $routeMatch->getMatchedRouteName();
//        
//        // different index for ILL tab
//        if (preg_match('/interlending/', strtolower($route)) && 
//                isset($this->config->get('config')->Index->url2)) {
//            $url = $this->config->get('config')->Index->url2;
//            
//            if (isset($this->config->get('config')->Index->default_core2)) {
//                $core = $this->config->get('config')->Index->default_core2;
//            }
//        }         
//        if (is_object($url)) {
//            return array_map(
//                function ($value) use ($core) {
//                    return "$value/$core";
//                },
//                $url->toArray()
//            );
//        }
//        return "$url/$core";
//    }
}