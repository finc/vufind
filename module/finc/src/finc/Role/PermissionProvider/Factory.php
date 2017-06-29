<?php
/**
 * Permission Provider Factory Class
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
 * @package  Authorization
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace finc\Role\PermissionProvider;
use Zend\ServiceManager\ServiceManager;

/**
 * Permission Provider Factory Class
 *
 * @category VuFind
 * @package  Authorization
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 *
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Factory for Username
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Username
     */
    public static function getCatUserType(ServiceManager $sm)
    {
        return new CatUserType(
            $sm->getServiceLocator()->get('ZfcRbac\Service\AuthorizationService'),
            $sm->getServiceLocator()->get('VuFind\ILSAuthenticator')
        );
    }

    /**
     * Factory for IpRangeFoFor
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return IpRangeFoFor
     */
    public static function getIpRangeFoFor(ServiceManager $sm)
    {
        return new IpRangeFoFor(
            $sm->getServiceLocator()->get('Request'),
            $sm->getServiceLocator()->get('VuFind\IpAddressUtils')
        );
    }

    /**
     * Factory for IpRegExFoFor
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return IpRegExFoFor
     */
    public static function getIpRegExFoFor(ServiceManager $sm)
    {
        return new IpRegExFoFor(
            $sm->getServiceLocator()->get('Request'));
    }
}
