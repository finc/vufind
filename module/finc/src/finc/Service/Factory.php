<?php
/**
 * Factory for various top-level VuFind services.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Service
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\Service;
use Zend\ServiceManager\ServiceManager;

/**
 * Factory for various top-level VuFind services.
 *
 * @category VuFind
 * @package  Service
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 *
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Construct the cache manager.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return \finc\Cache\Manager
     */
    public static function getCacheManager(ServiceManager $sm)
    {
        return new \finc\Cache\Manager(
            $sm->get('VuFind\Config')->get('config'),
            $sm->get('VuFind\Config')->get('searches')
        );
    }

    /**
     * Construct the branches reader.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return \finc\Config\BranchesReader
     */
    public static function getBranchesReader(ServiceManager $sm)
    {
        return new \finc\Config\BranchesReader(
            $sm->get('VuFind\CacheManager')
        );
    }

    /**
     * Construct the ILS connection.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return \VuFind\ILS\Connection
     */
    public static function getILSConnection(ServiceManager $sm)
    {
        $catalog = new \finc\ILS\Connection(
            $sm->get('VuFind\Config')->get('config')->Catalog,
            $sm->get('VuFind\ILSDriverPluginManager'),
            $sm->get('VuFind\Config')
        );
        return $catalog->setHoldConfig($sm->get('VuFind\ILSHoldSettings'));
    }

    /**
     * Construct the ILS hold logic.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return \VuFind\ILS\Logic\Holds
     */
    public static function getILSHoldLogic(ServiceManager $sm)
    {
        return new \finc\ILS\Logic\Holds(
            $sm->get('VuFind\ILSAuthenticator'), $sm->get('VuFind\ILSConnection'),
            $sm->get('VuFind\HMAC'), $sm->get('VuFind\Config')->get('config')
        );
    }

    /**
     * Construct the cookie manager.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return \VuFind\Cookie\CookieManager
     */
    public static function getCookieManager(ServiceManager $sm)
    {
        $config = $sm->get('VuFind\Config')->get('config');
        $path = '/';
        if (isset($config->Cookies->limit_by_path)
            && $config->Cookies->limit_by_path
        ) {
            $path = $sm->get('Request')->getBasePath();
            if (empty($path)) {
                $path = '/';
            }
        }
        $secure = isset($config->Cookies->only_secure)
            ? $config->Cookies->only_secure
            : false;
        $domain = isset($config->Cookies->domain)
            ? $config->Cookies->domain
            : null;
        $session_name = isset($config->Cookies->session_name)
            ? $config->Cookies->session_name
            : null;
        return new \finc\Cookie\CookieManager(
            $_COOKIE, $path, $domain, $secure, $session_name
        );
    }
}
