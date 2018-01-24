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
namespace Bsz\Search\Solr;
/**
 * Adapted version of Solr options with Client model
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Options extends \VuFind\Search\Solr\Options {
    
    /**
     *
     * @var Bsz\Client;
     */
    protected $Client;
 
    public function __construct(\VuFind\Config\PluginManager $configLoader, \Bsz\Config\Client $Client)
    {
        $this->Client = $Client;
        parent::__construct($configLoader);
    }
    
    /**
     * Get an array of hidden filters.
     *
     * @return array
     */
    public function getHiddenFilters()
    {
        $hidden = $this->hiddenFilters;
        $or = [];
        if (isset($this->Client) && count($this->Client->getIsils()) > 0) {
            foreach($this->Client->getIsils() as $isil) {
                $or[] = 'institution_id:'.$isil;            
                
            }
        }
        if (isset($this->Client) && $this->Client->hasFisBildung()) {
            $or[] = 'consortium:"FIS Bildung"';                        
        }
        $hidden[] = implode(' OR ',$or);
        
        return $hidden;
    }

    public function getDefaultFilters() {
        $parent = parent::getDefaultFilters();
        return $parent;
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
