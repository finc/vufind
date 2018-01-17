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

namespace Bsz\RecordTab;
use VuFind\Search\SearchRunner;

/**
 * Tab for Display of other volumes of the same serie
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Volumes extends \VuFind\RecordTab\AbstractBase {
    
    /**
     *
     * @var \Vu
     */
    protected $runner;
    
    /**
     *
     * @var array
     */
    protected $content;
    
    /**
     * @var string
     */
    protected $searchClassId;
    
    /**
     * Constructor
     * @param SearchRunner $runner
     */
    public function __construct(SearchRunner $runner) {
        $this->runner = $runner;
    }
    /**
     * Get the on-screen description for this tab
     * @return string
     */
    public function getDescription() {
        return 'Volumes';
    }
    
    /**
     * 
     * @return array|null
     */
    public function getContent() {
        if($this->content === null) {
            $relId = $this->driver->tryMethod('getIdsRelated');   
            $this->content = []; 
            if(is_array($relId) && count($relId) > 0) {
                foreach($relId as $k => $id) {
//                    $relId[$k] = 'id_related_host_item:"'.$id.'"';            
                    $relId[$k] = 'id_related:"'.$id.'"';                    
                }
                preg_match('/\((.*?)\)/', $this->driver->getUniqueId(), $matches);
                $isil = $matches[1];
                $params = [
                    'sort' => 'publish_date_sort desc',
                    'lookfor' => implode(' OR ', $relId),
                    'filter'  => 'material_content_type:Book',
                    'limit'   => 1000,
                ];
                if(isset($this->searchClassId)) {
                    $results = $this->runner->run($params, $this->searchClassId);                    
                } else {
                    $results = $this->runner->run($params);                                        
                }
                
                $results instanceof \VuFind\Search\Solr\Results;
                $this->content = $results->getResults();
            }   
        }
        return $this->content;
    }
    
    /**
     * This Tab is Active for collections or parts of collections only. 
     * @return boolean
     */
    public function isActive() {
        //getContents to determine active state
        $this->getContent();
        if(($this->driver->isCollection() || $this->driver->isPart()
                || $this->driver->isMonographicSerial()) && !empty($this->content)) {
            
            return true;
        }
        return false;
    }
    
    public function setSearchClassId($searchClassId) {
        if(isset($searchClassId) && !empty($searchClassId)) {
            $this->searchClassId = $searchClassId;            
        }
    }
}
