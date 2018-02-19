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

namespace BszTheme\View\Helper;
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
        return new Client($client);
    }
    
    public static function getClientAsset(ServiceManager $sm) 
    {
        $client = $sm->getServiceLocator()->get('bsz\config\client');
        
        $website = $client->getWebsite();
        
        $host = $sm->getServiceLocator()->get('Request')->getHeaders()->get('host')->getFieldValue();
        $parts = explode('.', $host);
        $tag = isset($parts[0]) ? $parts[0] : 'swb';     
        $library = null;
        $libraries = $sm->getServiceLocator()->get('bsz\config\libraries');
        if ($libraries instanceof  \Bsz\Config\Libraries) {
            if ($client->isIsilSession() && $client->hasIsilSession()) {     
                $isils = $client->getIsils();
                $library = $libraries->getFirst($isils);     
                $website = $library->getHomepage();  
            }
        }              
        return new ClientAsset($tag, $website, $library);
    }
    /**
     * Get Interlending View Helper
     * @param ServiceManager $sm
     * @return \Bsz\View\Helper\Bsz\View\Helper\Interlending
     */
    public static function getLibraries(ServiceManager $sm) 
    {
        $libraries = $sm->getServiceLocator()->get('bsz\libraries');
        return new Libraries($libraries);
    }
//    

}
