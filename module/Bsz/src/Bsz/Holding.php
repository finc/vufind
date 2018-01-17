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

namespace Bsz;
use VuFind\Search\SearchRunner as Runner;

/**
 * class for the BSZ holdings service
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Holding {
    
     /**
     *
     * @var array
     */
    protected $isxns = [];
    /**
     *
     * @var string
     */
    protected $network;
    /**
     *
     * @var int
     */
    protected $year;
    /**
     *
     * @var string
     */
    protected $title;
    /**
     *
     * @var string
     */
    protected $author;
    /**
     *
     * @var string
     */
    protected $zdbId;
    /**
     *
     * @var Runner
     */
    protected $runner;
    
    /**
     *
     * @var bool
     */
    protected $debug;
    
    /**
     * 
     * @param \Bsz\client $client
     */
    public function __construct(Runner $runner) 
    {
        $this->runner = $runner;
    }
    /**
     * Set ISBNs 
     * 
     * @param string $isxns
     * 
     * @return \Bsz\Holding
     */
    public function setIsxns($isxns) 
    {
        if (!is_array($isxns)) {
            $isxns = (array)$isxns;
        }
        foreach ($isxns as $isxn) {
            // strip non numeric chars
            $isxn = preg_replace('/[^0-9]/', '', $isxn);
            if (strlen($isxn) > 0 && is_numeric($isxn)) {
                $this->isxns[] = $isxn;
            }
        }
        $this->isxns = array_unique($this->isxns);
        return $this;
    }
    
    /**
     * Set title
     * 
     * @param string $title
     * 
     * @return Bsz\Holding
     */
    public function setTitle($title)
    {
        $this->title = urldecode($title);
        
        return $this;
    }
    
    /**
     * Set primary author
     * 
     * @param string $authos
     * 
     * @return Bsz\Holding
     */
    public function setAuthor($authos) 
    {
        $this->author = urldecode($authos);
        
        return $this;
    }
/**
 * Set Network
 * 
 * @param string $network SWB|GBV|KOBV|...
 * 
 * @return \Bsz\Holding
 */
    public function setNetwork($network) 
    {
        $this->network = strtoupper($network);

        return $this;
    }
    
    /**
     * Set Year
     * 
     * @param type $year
     * 
     * @return Bsz\Holding
     */
    public function setYear($year) 
    {
        if ((int)$year > 1800) {
            $this->year = (int)$year;
        }
        return $this;
    }
    /**
     * Set ZDB ID for good journal search results
     * 
     * @param string $zdb
     * 
     * @return $this
     */
    public function setZdbId($zdb)
    {
        if (!empty($zdb)) {
            $this->zdbId = $zdb;
        }
        return $this;
    }
    
    
    /**
     * Query solr
     * 
     * @return array
     */
    public function query() 
    {
        $params1 = $params2 = '';
        $tmp = [];
        if (isset($this->network)) {
            $params1 = 'consortium:' . $this->network;
        }        
        if (!empty($this->title)) {
            $tmp[] = 'title:"'.$this->title.'"';
        }
        if (!empty($this->author)) {
            $tmp[] = 'author:"'.$this->author.'"';
        }
        if (!empty($this->year)) {
            $tmp[] = 'publish_date:'.$this->year;
        }
        if (!empty($this->zdbId)) {
            $tmp[] = 'zdb_id:'.$this->zdbId;
        }
                
        if (count($this->isxns) > 0) {
            $isxns = [];
            foreach($this->isxns as $isxn) {
                if(strlen($isxn) <= 9) {
                    $isxns[] = 'issn:' . $isxn;
                } elseif (strlen($isxn) > 9) {
                    $isxns[] = 'isbn:' . $isxn;
                }
            }
            $params2 = implode(' OR ',$isxns);
            $tmp[] = count($params2) > 1 ? '(' . $params2 . ')' : $params2;
        }
        $params['lookfor'] = implode(' AND ', $tmp);
        //Network is added as filter
        $params['filter'] = $params1;

        $results = $this->runner->run($params, 'Interlending');
        $results instanceof \Bsz\Search\Interlending\Results;
        
        return $this->parse($results);
    }
    /**
     * process the response
     * 
     * @param \Zend\Http\Response $response
     */
    public function parse(\VuFind\Search\Solr\Results $results) 
    {
        $return = [];
        if ($results->getResultTotal() > 0) {
            
            foreach ($results->getResults() as $record) {
                $libraries = [];
                $record instanceof \Bsz\RecordDriver\SolrGvimarc;
                $ppn = $record->getPPN();
                $f924 = $record->getField924(true, true);
                
                // iterate through all found 924 entries
                // ISILs are unified here - information is being dropped! 
                foreach ($f924 as $isil => $field) {
                    $libraries[] = [
                        'isil' => $isil,
                        'callnumber' => isset($field['g']) ? $field['g'] : '',
                        'issue' => isset($field['z']) ? $field['z'] : ''
                    ];
                }

                //Catch Errors in parsing
                if ($libraries === null) {
                    continue;
                }
                $return['holdings'][$ppn] = $libraries;
            }
            $return['numppn'] = count($return['holdings']);
            $return['numfound'] = count($libraries);

        }
        else {
            $return['numfound'] = 0;
        }
        return $return;

    }
    /**
     * Checks if all needed params are set. 
     * 
     * @return boolean
     */
    public function checkQuery() 
    { 
        if (isset($this->network)) {
            if (count($this->isxns) > 0 || isset($this->zdbId)) {
                return true;
            } else if (!empty($this->title) && !empty($this->author)) {
                return true;
            }            
        }
        return false;
        
    }
    /**
     * Check whether parallel editions exist
     * 
     * @param array $ppns
     * @param array $isil
     * s
     * @return array
     */
    public function getParallelEditions($ppns, $isils) 
    {
        $params = [];
        // filtering by solr does not work as some libraries have too many isils
//        foreach ($isils as $k => $isil) {
//            $filter[] = 'institution_id:'.$isil;            
//        }     

        foreach ($ppns as $k => $ppn) {
            // escape braces 
            $ppns[$k] = 'id:'.str_replace(['(', ')'], ['\(', '\)'], $ppn);
        }
        $orLookfor = implode(' OR ', $ppns);
        $lookfor[] = $orLookfor;
                
//        $params['filter'] = $filter;      
        $params['lookfor'] = $lookfor;
        $params['wt'] = 'json';

        $results = $this->runner->run($params, 'Interlending');
        return $results;       

    } 
  
}
