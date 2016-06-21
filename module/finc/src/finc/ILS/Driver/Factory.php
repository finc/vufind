<?php
/**
 * ILS Driver Factory Class
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2014.
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
 * @package  ILS_Drivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace finc\ILS\Driver;
use Zend\ServiceManager\ServiceManager;

/**
 * ILS Driver Factory Class
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 *
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Factory for FincILS driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return FincILS
     */
    public static function getFincILS(ServiceManager $sm)
    {
        $factory = new \ProxyManager\Factory\LazyLoadingValueHolderFactory($sm->getServiceLocator()->get('VuFind\ProxyConfig'));

        $callback = function (& $wrapped, $proxy) use ($sm) {
            $wrapped = $sm->getServiceLocator()->get('ZfcRbac\Service\AuthorizationService');

            $proxy->setProxyInitializer(null);
        };
        
        $fl = new FincILS(
            $sm->getServiceLocator()->get('VuFind\DateConverter'),
            $sm->getServiceLocator()->get('VuFind\SessionManager'),
            $sm->getServiceLocator()->get('VuFind\RecordLoader'),
            $sm->getServiceLocator()->get('VuFind\Search'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            $factory->createProxy('ZfcRbac\Service\AuthorizationService', $callback)
        );

        $fl->setCacheStorage(
            $sm->getServiceLocator()->get('VuFind\CacheManager')->getCache('object')
        );

        return $fl;
    }

    /**
     * Factory for DAIA driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return DAIA
     */
    public static function getDAIA(ServiceManager $sm)
    {
        $daia = new DAIA(
            $sm->getServiceLocator()->get('VuFind\DateConverter')
        );

        $daia->setCacheStorage(
            $sm->getServiceLocator()->get('VuFind\CacheManager')->getCache('object')
        );

        return $daia;
    }    
    
    /**
     * Factory for PAIA driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return PAIA
     */
    public static function getPAIA(ServiceManager $sm)
    {
        $paia = new PAIA(
            $sm->getServiceLocator()->get('VuFind\DateConverter'),
            $sm->getServiceLocator()->get('VuFind\SessionManager')
        );

        $paia->setCacheStorage(
            $sm->getServiceLocator()->get('VuFind\CacheManager')->getCache('object')
        );

        return $paia;
    }

}