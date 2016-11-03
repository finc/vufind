<?php
/**
 * Resolver Driver Factory Class
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
 * @package  Resolver_Drivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace VuFind\Resolver\Driver;
use Zend\ServiceManager\ServiceManager;

/**
 * Resolver Driver Factory Class
 *
 * @category VuFind
 * @package  Resolver_Drivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 *
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Factory for Threesixtylink record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Threesixtylink
     */
    public static function getThreesixtylink(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('Resolver');
        return new Threesixtylink(
            $config->Threesixtylink->url,
            $sm->getServiceLocator()->get('VuFind\Http')->createClient()
        );
    }

    /**
     * Factory for Ezb record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Ezb
     */
    public static function getEzb(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('Resolver');
        return new Ezb(
            $config->Ezb->url,
            $sm->getServiceLocator()->get('VuFind\Http')->createClient()
        );
    }

    /**
     * Factory for Sfx record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Sfx
     */
    public static function getSfx(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('Resolver');
        return new Sfx(
            $config->Sfx->url,
            $sm->getServiceLocator()->get('VuFind\Http')->createClient()
        );
    }

    /**
     * Factory for Redi record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Redi
     */
    public static function getRedi(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('Resolver');
        return new Redi(
            $config->Redi->url,
            $sm->getServiceLocator()->get('VuFind\Http')->createClient()
        );
    }
}
