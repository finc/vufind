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
use Zend\ServiceManager\ServiceManager;
use Zend\Session\Container;


/**
 * Description of Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory {
    
    /**
     * Factory for volumes tab
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Volumes
     */
    public static function getVolumes(ServiceManager $sm)
    {
        $last = '';
        if (isset($_SESSION['Search']['last']) ){
            $last = urldecode($_SESSION['Search']['last']);
        }   
        $isils = [];
        if (strpos($last, 'consortium=FL') === FALSE 
            && strpos($last, 'consortium=ZDB') === FALSE
        ) {
            $client = $sm->getServiceLocator()->get('Bsz\Config\Client');
            $isils = $client->getIsils();
        }


        $volumes = new Volumes($sm->getServiceLocator()->get('VuFind\SearchRunner'), $isils);

        
        return $volumes;
    }
    /**
     * Factory for articles tab
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Volumes
     */
    public static function getArticles(ServiceManager $sm)
    {
        $last = '';
        if (isset($_SESSION['Search']['last']) ){
            $last = urldecode($_SESSION['Search']['last']);
        }   
        $isils = [];
        if (strpos($last, 'consortium=FL') === FALSE 
            && strpos($last, 'consortium=ZDB') === FALSE
        ) {
            $client = $sm->getServiceLocator()->get('bsz\client');
            $isils = $client->getIsils();
        }
        
        $articles = new Articles($sm->getServiceLocator()->get('VuFind\SearchRunner'), $isils);
        $request = new \Zend\Http\PhpEnvironment\Request();
        $url = strtolower($request->getUriString());
        return $articles;
    }
    
    /**
     * Factory for libraries tab 
     * 
     * @param ServiceManager $sm
     * @return \Bsz\RecordTab\LibrariesTab
     */
    public static function getLibraries(ServiceManager $sm)
    {
        $libraries = $sm->getServiceLocator()->get('bsz\libraries');
        $client = $sm->getServiceLocator()->get('bsz/client');
        return new Libraries($libraries, !$client->is('disable_library_tab'));
    }
    /**
     * Factory for description tab
     * 
     * @param ServiceManager $sm
     * @return \Bsz\RecordTab\LibrariesTab
     */
    public static function getDescription(ServiceManager $sm)
    {
        $client = $sm->getServiceLocator()->get('bsz/client');
        return new Description(!$client->is('disable_description_tab'));
    }
        /**
     * Factory for HoldingsILS tab plugin.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return HoldingsILS
     */
    public static function getHoldingsILS(ServiceManager $sm)
    {
        // If VuFind is configured to suppress the holdings tab when the
        // ILS driver specifies no holdings, we need to pass in a connection
        // object:
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        if (isset($config->Site->hideHoldingsTabWhenEmpty) && $config->Site->hideHoldingsTabWhenEmpty
        ) {
            $catalog = $sm->getServiceLocator()->get('VuFind\ILSConnection');
        } else {
            $catalog = false;
        }
        return new HoldingsILS($catalog);
    }
}
