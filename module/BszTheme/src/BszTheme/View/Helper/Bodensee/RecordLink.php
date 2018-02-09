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

namespace BszTheme\View\Helper\Bodensee;

/**
 * Extension of Root RecordLink Helper
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class RecordLink extends \VuFind\View\Helper\Root\RecordLink {
    /**
     *
     * @var \VuFind\Config\config
     */
    protected $config;
    /**
     *
     * @var string
     */
    protected $baseUrl;
    
    public function __construct(\VuFind\Record\Router $router, \Zend\Config\Config $config)
    {
        parent::__construct($router);
        $this->config = $config;
    }
    
    /**
     * Returns Link for ILL portal
     * @param \Bsz\RecordDriver\SolrMarc $driver
     * @param $sigel
     * @return type
     */
    public function getInterlendingUrl(\Bsz\RecordDriver\SolrMarc $driver, $sigel) {
        if(!empty($sigel)) {
            $url = \Bsz\Debug::isDev() ? 
                'http://flportaltest.bsz-bw.de/servlet/locator?' :
                'http://flportal.bsz-bw.de/servlet/locator?';   
            $issns = $driver->getISSNs();
            $isbns = $driver->getISBNs();
            $params = [
                'sid' => $driver->getNetwork(),
                'title' => $driver->getShortTitle() . ' '.$driver->getSubtitle(),
    //            'date' => array_shift($Driver->getPublicationDates()),
                'callnumber' => $driver->getCallnumber(),
                'sigel' => $sigel,

            ];
            if(count($issns) > 0) {
                $params['issn'] = array_shift($issns);
            }
            elseif(count($isbns) > 0) {
                $params['isbn'] = array_shift($isbns);
            }
            return \Bsz\UrlHelper::getUrl($url, $params);                    }
        return '';
        

    }

    
    
    public function getCoverServiceUrls($driver) {
        $services = [];
        $sources = $this->config->get('CoverSources');      
                       
        foreach($sources as $source => $url) {
            $isxn = strlen($driver->getCleanISSN()) > 0 ? 
                    $driver->getCleanISSN() : $driver->getCleanISBN();
            if(strlen($isxn) > 0) {
                $services[$source] = sprintf($url, $isxn);                
            }
        }
        return $services;
    }
    
    public function linkPPN(\Bsz\RecordDriver\SolrMarc$driver) 
    {
        $id = $driver->getuniqueId();
        $pos = strpos($id, ')');
        $ppn = substr($id, $pos + 1);
        $recordHelper = $this->getView()->plugin('record');        
       
        if (!empty($this->baseUrl) && $driver->getNetwork() == 'SWB' 
            && $recordHelper->isAtFirstIsil()
        ) {
            // Show link to aDIS
            $link = str_replace('<PPN>', $ppn, $this->baseUrl);
            
            return $this->getView()->render('Helpers/ppn.phtml', ['ppn' => $ppn, 'link' => $link, 'label' => 'To library OPAC' ]); 
        } else {
            // show link to Verbundsystem
            switch ($driver->getNetwork()) {

                case 'ZDB': $link = sprintf('http://zdb-opac.de/DB=1.1/PRS=HOL/CMD?ACT=SRCHA&IKT=12&TRM=%s', $ppn);
                    break;
                case 'HEBIS': $link = sprintf('http://cbsopac.rz.uni-frankfurt.de/DB=2.1/PRS=HOL/CMD?ACT=SRCHA&IKT=12&TRM=%s', $ppn);
                    break;
                case 'GBV': $link = sprintf('http://gso.gbv.de/DB=2.1/PRS=HOL/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM=%s', $ppn);
                    break;
                case 'HBZ': $link = sprintf('http://193.30.112.134/F?func=find-b&request=%s&find_code=IDN&l_base=HBZ01', $ppn);
                    break;
                case 'KOBV': $link = sprintf('http://portal.kobv.de/uid.do?query=%s&index=internal&plv=2', $ppn);
                    break;
                case 'BVB': $link = str_replace('<<id>>', $ppn, 'https://opacplus.bib-bvb.de/TouchPoint_touchpoint/start.do?Query=205=%22<<id>>%22&Language=De&SearchProfile=');
                    break;
                case 'SWB': $link = sprintf('http://swb.bsz-bw.de/DB=2.1/PPNSET?PPN=%s&PRS=HOL&HILN=888&INDEXSET=1', $ppn);
                    break;
                default: $link = '';            
            }            
        }
        return $this->getView()->render('Helpers/ppn.phtml', ['ppn' => $ppn, 'link' => $link, 'label' => 'redi_link_text']);        
    }    
}
