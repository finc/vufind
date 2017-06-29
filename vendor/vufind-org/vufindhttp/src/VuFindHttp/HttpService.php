<?php

/**
 * VuFind HTTP service class file.
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
 * VuFind HTTP service.
 *
 * @category VuFind
 * @package  Http
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
class HttpService implements HttpServiceInterface
{
    /**
     * Regular expression matching a request to localhost.
     *
     * @var string
     */
    const LOCAL_ADDRESS_RE = '@^(localhost|127(\.\d+){3}|\[::1\])@';

    /**
     * Proxy configuration.
     *
     * @see \Zend\Http\Client\Adapter\Proxy::$config
     *
     * @var array
     */
    protected $proxyConfig;

    /**
     * Default client options.
     *
     * @var array
     */
    protected $defaults;

    /**
     * Default adapter
     *
     * @var \Zend\Http\Client\Adapter\AdapterInterface
     */
    protected $defaultAdapter = null;

    /**
     * Constructor.
     *
     * @param array $proxyConfig Proxy configuration
     * @param array $defaults    Default HTTP options
     *
     * @return void
     */
    public function __construct(array $proxyConfig = [],
        array $defaults = []
    ) {
        $this->proxyConfig = $proxyConfig;
        $this->defaults = $defaults;
    }

    /**
     * Set proxy options in a Curl adapter.
     *
     * @param \Zend\Http\Client\Adapter\Curl $adapter Adapter to configure
     *
     * @return void
     */
    protected function setCurlProxyOptions($adapter)
    {
        $adapter->setCurlOption(CURLOPT_PROXY, $this->proxyConfig['proxy_host']);
        if (!empty($this->proxyConfig['proxy_port'])) {
            $adapter
                ->setCurlOption(CURLOPT_PROXYPORT, $this->proxyConfig['proxy_port']);
        }
    }

    /**
     * Are we configured to use the CURL adapter?
     *
     * @return bool
     */
    protected function hasCurlAdapterAsDefault()
    {
        $default = isset($this->defaults['adapter'])
            ? $this->defaults['adapter']
            : ($this->defaultAdapter ? get_class($this->defaultAdapter) : '');
        return $default === 'Zend\Http\Client\Adapter\Curl';
    }

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
    public function proxify(\Zend\Http\Client $client, array $options = [])
    {
        if ($this->proxyConfig) {
            $host = $client->getUri()->getHost();
            if (!$this->isLocal($host)) {
                $proxyType = isset($this->proxyConfig['proxy_type'])
                    ? $this->proxyConfig['proxy_type'] : 'default';

                if ($proxyType == 'socks5') {
                    $adapter = new \Zend\Http\Client\Adapter\Curl();
                    // Apply standard proxy options for Curl adapter:
                    $this->setCurlProxyOptions($adapter);
                    // Add SOCKS5 settings:
                    $adapter->setCurlOption(CURLOPT_FOLLOWLOCATION, true);
                    $adapter->setCurlOption(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                    $client->setAdapter($adapter);
                } elseif ($proxyType == 'default') {
                    // If the user has manually configured a Curl adapter,
                    // configure it for proxy compatibility; otherwise, create
                    // a fresh Proxy adapter.
                    if ($this->hasCurlAdapterAsDefault()) {
                        $adapter = new \Zend\Http\Client\Adapter\Curl();
                        $this->setCurlProxyOptions($adapter);
                    } else {
                        $adapter = new \Zend\Http\Client\Adapter\Proxy();
                        $options = array_replace($this->proxyConfig, $options);
                        $adapter->setOptions($options);
                    }
                    $client->setAdapter($adapter);
                }
            }
        }
        return $client;
    }

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
    ) {
        if ($params) {
            $query = $this->createQueryString($params);
            if (strpos($url, '?') !== false) {
                $url .= '&' . $query;
            } else {
                $url .= '?' . $query;
            }
        }
        $client
            = $this->createClient($url, \Zend\Http\Request::METHOD_GET, $timeout);
        if ($headers) {
            $client->setHeaders($headers);
        }
        return $this->send($client);
    }

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
    ) {
        $client
            = $this->createClient($url, \Zend\Http\Request::METHOD_POST, $timeout);
        $client->setRawBody($body);
        $client->setHeaders(
            array_merge(
                ['Content-Type' => $type, 'Content-Length' => strlen($body)],
                $headers
            )
        );
        return $this->send($client);
    }

    /**
     * Post form data.
     *
     * @param string $url     Request URL
     * @param array  $params  Form data
     * @param float  $timeout Request timeout in seconds
     *
     * @return \Zend\Http\Response
     */
    public function postForm($url, array $params = [], $timeout = null)
    {
        $body = $this->createQueryString($params);
        return $this->post($url, $body, \Zend\Http\Client::ENC_URLENCODED, $timeout);
    }

    /**
     * Set a default HTTP adapter (primarily for testing purposes).
     *
     * @param \Zend\Http\Client\Adapter\AdapterInterface $adapter Adapter
     *
     * @return void
     */
    public function setDefaultAdapter(
        \Zend\Http\Client\Adapter\AdapterInterface $adapter
    ) {
        $this->defaultAdapter = $adapter;
    }

    /**
     * Return a new HTTP client.
     *
     * @param string $url     Target URL
     * @param string $method  Request method
     * @param float  $timeout Request timeout in seconds
     *
     * @return \Zend\Http\Client
     */
    public function createClient($url = null,
        $method = \Zend\Http\Request::METHOD_GET, $timeout = null
    ) {
        $client = new \Zend\Http\Client();
        $client->setMethod($method);
        if (!empty($this->defaults)) {
            $client->setOptions($this->defaults);
        }
        if (null !== $this->defaultAdapter) {
            $client->setAdapter($this->defaultAdapter);
        }
        if (null !== $url) {
            $client->setUri($url);
        }
        if ($timeout) {
            $client->setOptions(['timeout' => $timeout]);
        }
        $this->proxify($client);
        return $client;
    }

    /// Internal API

    /**
     * Return query string based on params.
     *
     * @param array $params Parameters
     *
     * @return string
     */
    protected function createQueryString(array $params = [])
    {
        if ($this->isAssocParams($params)) {
            return http_build_query($params);
        } else {
            return implode('&', $params);
        }
    }

    /**
     * Send HTTP request and return response.
     *
     * @param \Zend\Http\Client $client HTTP client to use
     *
     * @throws Exception\RuntimeException
     * @return \Zend\Http\Response
     *
     * @todo Catch more exceptions, maybe?
     */
    protected function send(\Zend\Http\Client $client)
    {
        try {
            $response = $client->send();
        } catch (\Zend\Http\Client\Exception\RuntimeException $e) {
            throw new Exception\RuntimeException(
                sprintf('Zend HTTP Client exception: %s', $e),
                -1,
                $e
            );
        }
        return $response;
    }

    /**
     * Return TRUE if argument is an associative array.
     *
     * @param array $array Array to test
     *
     * @return boolean
     */
    public static function isAssocParams(array $array)
    {
        foreach (array_keys($array) as $key) {
            if (!is_numeric($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return TRUE if argument refers to localhost.
     *
     * @param string $host Host to check
     *
     * @return boolean
     */
    protected function isLocal($host)
    {
        return preg_match(self::LOCAL_ADDRESS_RE, $host);
    }

}
