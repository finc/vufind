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

use Exception;
use Interop\Container\ContainerInterface;
use VuFind\RecordDriver\SolrDefaultFactory;

/**
 * Class Factory
 * @package Finc\RecordDriver
 * @category boss
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory extends SolrDefaultFactory
{
    /**
     * Default Factory
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed|object
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new Exception('Unexpected options sent to factory.');
        }

        $driver = new $requestedName(
            $container->get('VuFind\Config')->get('config'),
            null,
            $container->get('VuFind\Config')->get('searches')
        );
        $driver->attachSearchService($container->get('VuFind\Search'));
        return $driver;
    }

    /**
     * @param ContainerInterface $container
     * @return SolrAI
     */
    public function getSolrAI(ContainerInterface $container)
    {
        return new SolrAI(
            $container->get('VuFind\Config')->get('config'),
            $container->get('VuFind\Config')->get('SolrAi'),
            $container->get('VuFind\Config')->get('searches')
        );
    }

    /**
     * @param ContainerInterface $container
     * @return SolrIS
     */
    public function getSolrIS(ContainerInterface $container)
    {
        return new SolrIS(
            $container->get('VuFind\Config')->get('config'),
            null,
            null
        );
    }
}
