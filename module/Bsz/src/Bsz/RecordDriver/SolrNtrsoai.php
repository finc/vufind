<?php

/*
 * The MIT License
 *
 * Copyright 2016 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Bsz\RecordDriver;
use Bsz\FormatMapper;

/**
 * Description of SolrOai
 *
 * @author Stefan Winkler <stefan.winkler@bsz-bw.de>
 * 
 */
class SolrNtrsoai extends \VuFind\RecordDriver\SolrDefault {
    
    /**
     *
     * @var \Bsz\Config\Client
     */
    protected $client;
    /**
     *
     * @var SimpleXMLElement
     */
    protected $xml;
    /**
     *
     * @param FormatMapper $mapper
     * @param \Bsz\Config\Client $Client
     * @param type $mainConfig
     * @param type $recordConfig
     * @param type $searchSettings
     */
    public function __construct(FormatMapper $mapper, \Bsz\Config\Client $client, $mainConfig = null, $recordConfig = null,
        $searchSettings = null) {
        
        parent::__construct($mapper, $mainConfig, $recordConfig, $searchSettings);
        $this->mapper = $mapper;
        $this->client = $client;
    }
      
    /**
     * Is this a Oai record
     * @return boolean
     */
    public function isNtrsOai() {
        return true;
    } 
    
    /**
     * Is this a DLR-Koha record
     * @return boolean
     */
    public function isDlrKoha() {
        return false;
    } 
    
    /**
     * Attach a Search Results Plugin Manager connection and related logic to
     * the driver
     *
     * @param \VuFind\SearchRunner $runner
     * @return void
     */
    public function attachSearchRunner(\VuFind\Search\SearchRunner $runner)
    {
        $this->runner = $runner;
    }  
    
    public function parseOAI()
    {   
        $xml = $this->getXML('oai_dc');

    }
    
    /**
     * Set raw data to initialize the object.
     *
     * @param mixed $data Raw data representing the record; Record Model
     * objects are normally constructed by Record Driver objects using data
     * passed in from a Search Results object.  The exact nature of the data may
     * vary depending on the data source -- the important thing is that the
     * Record Driver + Search Results objects work together correctly.
     *
     * @return void
     */
    public function setRawData($data)
    {
        $this->fields = $data;
        $this->xml = simplexml_load_string($this->fields['fullrecord']);
    }
    
    /**
     * Parse the date out of oai data
     * @return array
     */
    public function getPublicationDates() 
    {
        $dates = $this->getDcFields('date');
        // if we got a known format, parse this
        if (isset($dates[0]) &&  strlen($dates[0]) == 8) {
            $year = substr($dates[0], 0, 4);
            $month = substr($dates[0], 4, 2);
            $day = substr($dates[0], 6, 2);
            $date = new \DateTime($year.'-'.$month.'-'.$day);
            return [$date->format('d.m.Y')];            
        }
        return $dates; 
    }
    /**
     * 
     * @param string $field
     * @return array
     */
    protected function getDcFields($field) {
       return $this->xml->xpath('dc:'.$field);
    }
    
    /**
     * Returns an array with url and desc keys to link the document id. 
     * @return array
     */
    public function getDokumentLink() 
    {
        $link = [];
        $id = parent::getUniqueID();
        $split = explode(':', $id);
        if (strpos($split[1], 'nasa') !== FALSE) {
            $link['url'] = 'http://ntrs.nasa.gov/search.jsp?R='.end($split);
        } else {
            $link['url'] = 'http://elib.dlr.de/'.end($split);            
        }
        $link['desc'] = end($split);
        return $link;
    }
    
    public function getCopyright() 
    {
        $copy = $this->getDcFields('coverage');
        return array_shift($copy);
    }
    
    public function getSource() 
    {
        $source = $this->getDcFields('source');
        return array_shift($source);
    }
    
    /**
     * Get default OpenURL parameters.
     * this is slightly changed compared to VuFind original
     *
     * @return array
     */
    protected function getDefaultOpenUrlParams()
    {
        // Get a representative publication date:
        $pubDate = $this->getPublicationDates();
        $pubDate = empty($pubDate) ? '' : $pubDate[0];

        // Start an array of OpenURL parameters:
        return [
            'url_ver' => 'Z39.88-2004',
            'ctx_ver' => 'Z39.88-2004',
            'ctx_enc' => 'info:ofi/enc:UTF-8',
            'rfr_id' => 'info:sid/' . $this->getCoinsID() . ':generator',
            'rft.date' => $pubDate
        ];
    }

    /**
     * get all formats from solr field format
     * @return array
     */
    public function getFormats() 
    {
        $formats = [];
        if (isset($this->fields['format'])) {
            $formats = $this->fields['format'];
        }
//        // Vorläufiger Workaround um die Reihen auf Berichte zu mappen
//        $keys = array_keys($formats, 'Serial');
//        foreach ($keys as $key) {
//            $formats[$key] = 'Report';
//        }
        return $formats;
    }

    /**
     * get Institutes and Institutions from solr field
     * 
     * @return array
     */
    public function getInstitutes() 
    {   
        
        $institutes = [];
        if (isset($this->fields['institute'])) {
            $institutes = array_filter($this->fields['institute']);
        }
        
        return array_unique($institutes, SORT_STRING);
    }   
    
    /**
     * Source elib?
     * @return boolean
     */
    protected function isElib() 
    {
        if (isset($this->fields['institution_id']) && 
                in_array('elib', $this->fields['institution_id'])) {
            return true;
        }
        return false;
            
    }
    
    /**
     * Source NASA? 
     * @return boolean
     */
    protected function isNTRS()
    {
        if (isset($this->fields['institution_id']) && 
                in_array('NTRS', $this->fields['institution_id'])) {
            return true;
        }
        return false;
        
    }
    
    
    
        /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getURLs()
    {
        //url = 856u:555u

        $urls = [];
        $urls = parent::getURLs();
        foreach ($urls as $key => $url) {
            // different descriptions for elib and NTRS
            if (!array_key_exists('desc', $url) && $this->isElib()) {
                switch ($key) {
                    case 0: $url['desc'] = 'to_elib_record';
                        break;
                    default: $url['desc'] = 'More Information';
                }
            } elseif (!array_key_exists('desc', $url) && $this->isNTRS()) {
                $url['desc'] = 'Full Text';
            }
            $urls[$key] = $url;                
            
        }
        return $urls;
    }

    /**
     * For rticles: get container title
     * @return type
     */
    public function getContainerTitle()
    {
        return '';
    }

    /**
     * For rticles: get container title
     * @return type
     */
    public function getContainer()
    {
        return array();
    }

    /**
     * Get the Container issue from different fields
     * @return string
     */
    public function getContainerIssue()
    {
        // not supported for OAI data:
        return '';
    }

    /**
     * Get container pages from different fields
     * @return string
     */
    public function getContainerPages()
    {
        // not supported for OAI data:
        return '';
    }

    /**
     * get container year from different fields
     * @return string
     */
    public function getContainerYear()
    {
        // not supported for OAI data:
        return '';
    }
    
    /**
     * get container year from different fields
     * @return array
     */
    public function getRelatedItems() 
    {
        // not supported for OAI data:
        return array();
    }
}

