<?php
/**
 * Solr aspect of the Search Multi-class (Options)
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2011.
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
 * @package  Search_Interlending
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
namespace Bsz\Search\Interlending;

class Options extends \VuFind\Search\Solr\Options
{
    /**
     *
     * @var Bsz\Config\Client;
     */
    protected $client;
    
    /**
     * Overrides Solr Konstruktor
     * @param \VuFind\Config\PluginManager $configLoader
     * @param \Bsz\Config\Client $Client
     */
    public function __construct(\VuFind\Config\PluginManager $configLoader, \Bsz\Config\Client $client) {
        parent::__construct($configLoader);
        $this->client = $client;      
        
    }
    /**
     * 
     * @return string
     */
    public function getSearchAction() {
        return 'interlending-results';
    }  
    /**
     * 
     * @return string
     */
    public function getSearchHomeAction() {
        return 'interlending-home';
    }  
    /**
     * 
     * @return string
     */
    public function getAdvancedSearchAction() {
        return 'interlending-advanced';
    } 
    
    /**
     * Filter out current institution ID
     * @return string
     */
    public function getHiddenFilters() {
        $hidden = $this->hiddenFilters;
        $hidden[] = '-consortium:"FIS Bildung"';  
        $isils = $this->client->getIsilInterlending();
        $or = [];
        foreach ($isils as $isil){
            $or[] = 'institution_id:'.$isil;
        }
        $hidden[] = implode(' OR ',$or);
        return $hidden;
    }
    
        /**
     * This avoids confusion when dealing with different search limits
     * in BOSS, a user can't change the limit
     * @return int
     */
    public function getLastLimit() {
        return 20;
    }

 
}