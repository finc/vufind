<?php
/**
 * Record Driver Factory Class
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2014.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace finc\RecordDriver;
use Zend\ServiceManager\ServiceManager;

/**
 * Record Driver Factory Class
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 *
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Factory for SolrDefault record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrDefault
     */
    public static function getSolrDefault(ServiceManager $sm)
    {
        $driver = new SolrDefault(
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
        );
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
    public static function getSolrMarc(ServiceManager $sm)
    {
        $driver = new SolrMarc(
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
     * Factory for SolrMarcRemote record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarcRemote
     */
    public static function getSolrMarcRemote(ServiceManager $sm)
    {
        $driver = new SolrMarcRemote(
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
     * Factory for SolrAI record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrAI
     */
    public static function getSolrAI(ServiceManager $sm)
    {
        return new SolrAI(
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('SolrAI'),
            null
        );
    }

    /**
     * Factory for SolrMarcRemoteFinc record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarcRemoteFinc
     */
    public static function getSolrMarcRemoteFinc(ServiceManager $sm)
    {
        $driver = new SolrMarcRemoteFinc(
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
     * Factory for SolrMarcFinc record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarcFinc
     */
    public static function getSolrMarcFinc(ServiceManager $sm)
    {
        $driver = new SolrMarcFinc(
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
}
