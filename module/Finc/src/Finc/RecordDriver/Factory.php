<?php

/*
 * Copyright (C) 2019 Bibliotheksservice Zentrum Baden-Württemberg, Konstanz
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Finc\RecordDriver;

use Interop\Container\ContainerInterface,
    \VuFind\RecordDriver\SolrDefaultFactory;

/**
 * Factory fo DLR RecordDrivers
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */

class Factory extends SolrDefaultFactory {
    
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }

        $requestedName = $requestedName;
             
        $driver = new $requestedName(
            $container->get('Bsz\Config\Client'),
            null,
            $container->get('VuFind\Config')->get('searches')
        );
        //$driver->attachILS(
        //    $container->get(\VuFind\ILS\Connection::class),
        //    $container->get(\VuFind\ILS\Logic\Holds::class),
        //    $container->get(\VuFind\ILS\Logic\TitleHolds::class)
        //);
        
        $driver->attachSearchService($container->get('VuFind\Search'));
        $driver->attachSearchRunner($container->get('VuFind\SearchRunner'));
       return $driver;
    }
}
