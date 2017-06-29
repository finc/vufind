<?php

/**
 * VuFind HTTP service interface definition.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Http
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
namespace VuFindHttp;

/**
 * VuFind HTTP service interface definition.
 *
 * @category VuFind
 * @package  Http
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
interface HttpServiceInterface
{
    /**
     * Proxify an existing client.
     *
     * Returns the client given as argument with appropriate proxy setup.
     *
     * @param \Zend\Http\Client $client  HTTP client
     * @param array             $options ZF2 ProxyAdapter options
     *
     * @return \Zend\Http\Client
     */
    public function proxify(\Zend\Http\Client $client, array $options = []);

    /**
     * Perform a GET request.
     *
     * @param string $url     Request URL
     * @param array  $params  Request parameters
     * @param float  $timeout Request timeout in seconds
     * @param array  $headers Request headers
     *
     * @return \Zend\Http\Response
     */
    public function get($url, array $params = [], $timeout = null,
        array $headers = []
    );

    /**
     * Perform a POST request.
     *
     * @param string $url     Request URL
     * @param mixed  $body    Request body document
     * @param string $type    Request body content type
     * @param float  $timeout Request timeout in seconds
     * @param array  $headers Request http-headers
     *
     * @return \Zend\Http\Response
     */
    public function post($url, $body = null, $type = 'application/octet-stream',
        $timeout = null, array $headers = []
    );

    /**
     * Post form data.
     *
     * @param string $url     Request URL
     * @param array  $params  Form data
     * @param float  $timeout Request timeout in seconds
     *
     * @return \Zend\Http\Response
     */
    public function postForm($url, array $params = [], $timeout = null);

    /**
     * Return a new proxy client.
     *
     * @param string $url     Target URL
     * @param string $method  Request method
     * @param float  $timeout Request timeout in seconds
     *
     * @return \Zend\Http\Client
     */
    public function createClient($url = null,
        $method = \Zend\Http\Request::METHOD_GET, $timeout = null
    );

}
