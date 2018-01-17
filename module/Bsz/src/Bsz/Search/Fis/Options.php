<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Bsz\Search\Fis;

/**
 * Description of Options
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Options extends \VuFind\Search\Solr\Options {
    
    
        /**
     *
     * @var Bsz\Client;
     */
    protected $Client;
    
    /**
     * Overrides Solr Konstruktor
     * @param \VuFind\Config\PluginManager $configLoader
     * @param \Bsz\Config\Client $Client
     */
    public function __construct(\VuFind\Config\PluginManager $configLoader, \Bsz\Config\Client $Client) {
        parent::__construct($configLoader);
        $this->Client = $Client;       
        
    }
    /**
     * 
     * @return string
     */
    public function getSearchAction() {
        return 'fis-results';
    }  
    /**
     * 
     * @return string
     */
    public function getSearchHomeAction() {
        return 'fis-home';
    }  
    /**
     * 
     * @return string
     */
    public function getAdvancedSearchAction() {
        return 'fis-advanced';
    } 
    
    /**
     * Filter out current institution ID
     * @return string
     */
        /**
     * Filter out current institution ID
     * @return string
     */
    public function getHiddenFilters() {
        $hidden = $this->hiddenFilters;
        //negate the Filter to exclude books from the selected institution
        $hidden[] = 'consortium:"FIS Bildung"';            

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
