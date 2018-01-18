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

namespace Bsz\View\Helper;
use Zend\ServiceManager\ServiceManager;

/**
 * Description of Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory {
     /**
     * Get Client View Helper
     * @param ServiceManager $sm
     * @return \Bsz\View\Helper\Client
     */
    public static function getClient(ServiceManager $sm) 
    {        
        $client = $sm->getServiceLocator()->get('bsz\client');
        return new \Bsz\View\Helper\Client($client);
    }
    /**
     * Get Interlending View Helper
     * @param ServiceManager $sm
     * @return \Bsz\View\Helper\Bsz\View\Helper\Interlending
     */
    public static function getLibraries(ServiceManager $sm) 
    {
        $libraries = $sm->getServiceLocator()->get('bsz\libraries');
        return new \Bsz\View\Helper\Libraries($libraries);
    }
    
    public static function getIllForm(ServiceManager $sm) 
    {
        $request = $sm->getServiceLocator()->get('request');
        // params from form submission
        $params = $request->getPost()->toArray();
        // params from open url
        $openUrlParams = $request->getQuery()->toArray();
        $parser = $sm->getServiceLocator()->get('bsz\parser\openurl');            
        $parser->setParams($openUrlParams);
        // mapped openURL params
        $formParams = $parser->map2Form();
        // merge both param sets
        $mergedParams = array_merge($formParams, $params);
        return new IllForm($mergedParams);
        
    }
    
}
