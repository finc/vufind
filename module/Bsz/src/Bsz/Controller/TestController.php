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
        if (\Bsz\Debug::isInternal()) {
            phpinfo();
        }
        return $this->getResponse();
    }
}
