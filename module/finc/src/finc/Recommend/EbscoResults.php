<?php
/**
 * Recommendation Module Ebsco Results
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2014.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Viola Elsenhans <elsenhans@ub.uni-leipzig.de>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace finc\Recommend;

use LosReCaptcha\Service\Exception;

/**
 * Recommendation Module Factory Class
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 *
 * @codeCoverageIgnore
 */
class EbscoResults implements \VuFind\Recommend\RecommendInterface,
    \VuFindHttp\HttpServiceAwareInterface, \Zend\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Ebsco service 3rdparty base URL
     *
     * @var string
     * @access protected
     */
    protected $baseUrl;

    /**
     * Completed Ebsco service 3rdparty target URL to resolve
     *
     * @var string
     * @access protected
     */
    protected $targetUrl;

    /**
     * Search string
     *
     * @var string
     * @access protected
     */
    protected $lookfor;

    /**
     * Namespace of queried institution defined by 3rdparty service provider
     *
     * @var string
     * @access protected
     */
    protected $namespace;

    /**
     * Search results
     *
     * @var array
     */
    protected $results;

    /**
     * Constructor
     *
     * @param array $isils Institution info
     *
     * @access public
     * @throws \Exception   Service is only available for one generic defined isil.
     */
    public function __construct($isils = [])
    {
        if (count($isils) != 1) {
            throw new Exception(
                'Service is only available for one generic defined isil.'
                . ' Please use full configuration option at searches.ini');
        }
        $this->namespace = $isils[0];
    }

    /**
     * Store the configuration of the recommendation module.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     * @access public
     */
    public function setConfig($settings)
    {
        // Parse out parameters:
        $params = explode(':', $settings);
        $this->baseUrl = (isset($params[0]) && !empty($params[0]))
            ? $params[0] : 'www.bibliothek.tu-chemnitz.de/finc/%s/ebsco3.cgi';
        $this->namespace = (isset($params[1]) && !empty($params[1]))
            ? $params[1] : $this->namespace;
    }

    /**
     * Called at the end of the Search Params objects' initFromRequest() method.
     * This method is responsible for setting search parameters needed by the
     * recommendation module and for reading any existing search parameters that may
     * be needed.
     *
     * @param \VuFind\Search\Base\Params $params Search parameter object
     * @param \Zend\StdLib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     * @access public
     */
    public function init($params, $request)
    {
        // Collect the best possible search term(s):
        $this->lookfor = $request->get('lookfor', '');
        if (empty($this->lookfor) && is_object($params)) {
            $this->lookfor = $params->getQuery()->getAllTerms();
        }
        $this->lookfor = urlencode(trim($this->lookfor));
        $this->targetUrl = $this->getURL(
            'https://' . $this->baseUrl, strtolower($this->namespace)
        );
    }

    /**
     * Called after the Search Results object has performed its main search.  This
     * may be used to extract necessary information from the Search Results object
     * or to perform completely unrelated processing.
     *
     * @param \VuFind\Search\Base\Results $results Search results object
     *
     * @return void
     * @access public
     * @throws Exception    JSON type error
     */
    public function process($results)
    {
        // Make the HTTP request:
        $results = $this->getHttpClient($this->targetUrl)->send();

        if (!$results->isSuccess()) {
            $this->results = false;
        }
        $this->results = $this->sortByHits(
            json_decode($results->getBody(), true)
        );
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception ('JSON type error ' . json_last_error_msg());
        }
    }

    /**
     * Get an HTTP client
     *
     * @param string $url URL for client to use
     *
     * @return \Zend\Http\Client
     * @throws \Exception Http service missing.
     */
    protected function getHttpClient($url)
    {
        if (null === $this->httpService) {
            throw new \Exception('HTTP service missing.');
        }
        return $this->httpService->createClient($url);
    }

    /**
     * Get the results of the subject query -- false if none, otherwise an array
     * with 'worksArray' and 'subject' keys.
     *
     * @return bool|array
     */
    public function getResult()
    {
        return $this->results;
    }

    /**
     * Build the url which will be send to retrieve the RSS results
     *
     * @param string $targetUrl Base URL
     * @param string $namespace Namespace of institution
     *
     * @return string The url to be sent
     * @access protected
     */
    protected function getURL($targetUrl, $namespace)
    {
        return (sprintf($targetUrl, $namespace)) . "?q=" . urlencode($this->lookfor);
    }

    /**
     * Sort databases by hits
     *
     * @param array  $results   Unprocessed array of curl requrest.
     * @param string $sortOrder Order of sort. Default: "SORT_DESC"
     *
     * @return array $results
     * @access protected
     */
    protected function sortByHits($results, $sortOrder = SORT_DESC)
    {
        $databases = $results['results'];
        foreach ($databases as $key => $row) {
            $hits[$key] = $row['hits'];
        }
        array_multisort($hits, $sortOrder, $databases);
        $results['results'] = $databases;

        return $results;
    }

}