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

namespace Bsz\Controller;
use Bsz\Debug;
use Zend\Http\Client;

/**
 * Hier kann man alles testen
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class TestController extends \VuFind\Controller\AbstractBase {
    
    public function __construct() {
        
        error_reporting(E_ALL);

    }    
   /**
     * Action to simply test some methods
     */
    public function recordAction() {
        
        $runner = $this->getServiceLocator()->get('VuFind\SearchRunner');
        $ppn = $this->params()->fromQuery('ppn');
        if (!empty($ppn)) {
            $params['lookfor'] = 'id:'.str_replace(['(', ')'], ['\(', '\)'], $ppn);
            $results = $runner->run($params, 'Interlending');

            // now, we can do something with our record
            foreach ($results->getResults() as $record) {
                $record instanceof \Bsz\RecordDriver\SolrGvimarc;
                var_dump($record->getTitle());
            }            
        } else {
            echo 'Param PPN is mandatory';
        }
        
        return $this->getResponse();
        
         
    }
    
    public function phpinfoAction() {
        if (Debug::isInternal()) {
            phpinfo();
        }
        return $this->getResponse();
    }
    
    public function zflAction() {        
        
        
        $params = [
            'Verfasser' =>  '',
            'Titel' =>  'BSZ-Testtitel',
            'Untertitel' =>  '',
            'Auflage' =>  '',
            'Verlag' =>  'Springer-Verlag <Berlin; Heidelberg>',
            'EOrt' =>  'Konstanz',
            'EJahr' =>  '2015',
            'BandTitel' =>  '',
            'Isbn' =>  '',
            'AufsatzAutor' =>  '',
            'AufsatzTitel' =>  '',
            'Seitenangabe' =>  '',
            'Bestellform' =>  'Leihen',
            'Sigel' =>  'Kon 4',
            'ErledFrist' =>  '2018-08-11',
            'AndereAuflage' =>  'on',
            'MaxKostenKopie' =>  '8',
            'Bemerkung' =>  '',
            'BenutzerNummer' =>  '09011551',
            'Verbund' =>  'SWB',
            'TitelId' =>  '479128995',
            'Besteller' =>  'E',
        ];
       
        $urlsToTest = [
            "https://fltest.bsz-bw.de/flcgi/pflauftrag.pl",
            "https://zfls-test.bsz-bw.de/flcgi/pflauftrag.pl",
            'https://git.bsz-bw.de'
        ];
        foreach ($urlsToTest as $uri)
        {
            echo '<h2>Testing: '.$uri;
            if (Debug::isInternal()) {
                $client = new Client();
                $client->setAdapter('\Zend\Http\Client\Adapter\Curl')
                    ->setUri($uri)
                    ->setMethod('POST')
                    ->setOptions(['timeout' => 5])
                    ->setParameterPost($params)

                $response = $client->send();
                var_dump($response->getContent());
            }
        }

    //avoid any templates being processed
    return $this->getResponse();
        
    }
}
