<?php
/**
 * Service Factory
 *
 * PHP version 5.3
 *
 * Copyright (C) Leipzig University Library 2016.
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
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace finc\Controller;

use Zend\ServiceManager\ServiceManager,
    VuFind\Controller\Factory as FactoryBase;

/**
 * Factory for various top-level VuFind services.
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 *
 * @codeCoverageIgnore
 */
class Factory extends FactoryBase
{
    /**
     * Construct a generic controller.
     *
     * @param string         $name Name of table to construct (fully qualified
     * class name, or else a class name within the current namespace)
     * @param ServiceManager $sm   Service manager
     *
     * @return object
     * @throws \Exception Cannot construct __CLASS__
     */
    public static function getGenericController($name, ServiceManager $sm)
    {
        // Prepend the current namespace unless we receive a FQCN:
        $class = (strpos($name, '\\') === false)
            ? static::getNamespace() . '\\' . $name : $name;
                 if (!class_exists($class)) {
                     throw new \Exception('Cannot construct ' . $class);
        }
        return new $class($sm->getServiceLocator());
      }

    /**
     * Get namespace of class
     *
     * @return string Namespace
     * @access private
     */
    private static function getNamespace()
    {
        return substr(
            static::class, 0,
            strrpos(static::class, '\\')
          );
    }


    /**
     * Construct the AmslResourceController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return AmslResourceController
     */
     public function getAmslResourceController(ServiceManager $sm)
     {
        return new AmslResourceController(
            $sm->getServiceLocator()->get('VuFind/Config')->get('Amsl'),
            $sm->getServiceLocator()->get('VuFind/Http')
        );
    }


    /**
     * Construct the DocumentDeliveryServiceController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return DocumentDeliveryServiceController
     */
    public static function getDocumentDeliveryServiceController(
        ServiceManager $sm
    )
    {
        $container = new \Zend\Session\Container(
            'DDS', $sm->getServiceLocator()->get('VuFind\SessionManager')
        );
        return new DocumentDeliveryServiceController(
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('DDS'),
            $container
        );
    }

    /**
     * Construct the RecordController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return RecordController
     */
    public static function getRecordController(ServiceManager $sm)
    {
        return new RecordController(
            $sm->getServiceLocator(),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config')
        );
    }
}
