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
namespace Bsz\RecordDriver;
use Zend\ServiceManager\ServiceManager;

/**
 * BSZ RecordDriverFactory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory extends \VuFind\RecordDriver\SolrDefaultFactory {
     /**
     * Factory for EDS record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return EDS
     */
    public static function getEDS(ServiceManager $sm)
    {
        $eds = $sm->getServiceLocator()->get('VuFind\Config')->get('EDS');
        return new EDS($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'), $eds, $eds
        );
    }
    /**
     * Factory for SolrMarc record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrGviMarc(ServiceManager $sm)
    {
        $driver = new SolrGviMarc($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('Bsz\Config\Client'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
        );
        return Factory::attach($driver, $sm);
    }
    /**
     * Factory for SWB record driver
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrGviMarcDE576(ServiceManager $sm)
    {
        $driver = new SolrGviMarcDE576($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('Bsz\Config\Client'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')                
        );
        return Factory::attach($driver, $sm);
    }
    
    /**
     * Factory for ZDB record driver
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrGviMarcDE600(ServiceManager $sm)
    {
        $driver = new SolrGviMarcDE600($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('Bsz\Config\Client'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')                
        );
        return Factory::attach($driver, $sm);
    }
    
    /**
     * Factory for GBV record driver
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrGviMarcDE601(ServiceManager $sm)
    {
        $driver = new SolrGviMarcDE601($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('Bsz\Config\Client'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')                
        );
        return Factory::attach($driver, $sm);
    }
    
    /**
     * Factory for KOBV record driver
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrGviMarcDE602(ServiceManager $sm)
    {
        $driver = new SolrGviMarcDE602($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('Bsz\Config\Client'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')                
        );
        return Factory::attach($driver, $sm);
    }
    
    /**
     * Factory for HEBIS record driver
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrGviMarcDE603(ServiceManager $sm)
    {
        $driver = new SolrGviMarcDE603($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('Bsz\Config\Client'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')                
        );
        return Factory::attach($driver, $sm);
    }
    
    /**
     * Factory for BVB record driver
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrGviMarcDE604(ServiceManager $sm)
    {
        $driver = new SolrGviMarcDE604($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('Bsz\Config\Client'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')                
        );
        return Factory::attach($driver, $sm);
    }
    
    /**
     * Factory for HBZ record driver
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrGviMarcDE605(ServiceManager $sm)
    {
        $driver = new SolrGviMarcDE605($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('Bsz\Config\Client'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')                
        );
        return Factory::attach($driver, $sm);
    }
    
    /**
     * Factory for DNB record driver
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrGviMarcDE101(ServiceManager $sm)
    {
        $driver = new SolrGviMarcDE101($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('Bsz\Config\Client'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')                
        );
        return Factory::attach($driver, $sm);
    }

    /**
     * Factory for K10plus record driver
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrGviMarcDE627(ServiceManager $sm)
    {
        $driver = new SolrGviMarcDE627($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('Bsz\Config\Client'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')                
        );
        return Factory::attach($driver, $sm);
    }
    
    /**
     * Factory for SolrMarc record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrMarc(ServiceManager $sm)
    {
        $driver = new SolrMarc($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
        );
        $driver->attachILS(
            $sm->getServiceLocator()->get('VuFind\ILSConnection'),
            $sm->getServiceLocator()->get('VuFind\ILSHoldLogic'),
            $sm->getServiceLocator()->get('VuFind\ILSTitleHoldLogic')
        );
        $driver->attachSearchService($sm->getServiceLocator()->get('VuFind\Search'));
        return $driver;
    }
    
    /**
     * Attach all the common stuff
     * 
     * @param \Bsz\RecordDriver\SolrMarc $driver
     * @return \Bsz\RecordDriver\SolrMarc
     */
    private static function attach(SolrMarc $driver, ServiceManager $sm)
    {
        $driver->attachILS(
            $sm->getServiceLocator()->get('VuFind\ILSConnection'),
            $sm->getServiceLocator()->get('VuFind\ILSHoldLogic'),
            $sm->getServiceLocator()->get('VuFind\ILSTitleHoldLogic')
        );
        //We use this to fetch containers - they are missing in out MARC record
        $driver->attachSearchRunner($sm->getServiceLocator()->get('VuFind\SearchRunner'));
        $driver->attachSearchService($sm->getServiceLocator()->get('VuFind\Search'));
        return $driver;
    }
    
        /**
     * Factory for SolrMarc record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrFindexMarc(ServiceManager $sm)
    {
        $driver = new SolrFindexMarc($sm->getServiceLocator()->get('Bsz\Mapper'), 
            $sm->getServiceLocator()->get('Bsz\Config\Client'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
        );
        return Factory::attach($driver, $sm);
    }
    

}

