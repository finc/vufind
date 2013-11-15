<?php

/**
 * Abstract factory for SOLR backends.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2013.
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
 * @category VuFind2
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace VuFind\Search\Factory;

use VuFind\Search\Solr\InjectHighlightingListener;
use VuFind\Search\Solr\InjectSpellingListener;
use VuFind\Search\Solr\MultiIndexListener;
use VuFind\Search\Solr\V3\ErrorListener as LegacyErrorListener;
use VuFind\Search\Solr\V4\ErrorListener;

use VuFindSearch\Backend\BackendInterface;
use VuFindSearch\Backend\Solr\QueryBuilder;
use VuFindSearch\Backend\Solr\HandlerMap;
use VuFindSearch\Backend\Solr\Connector;
use VuFindSearch\Backend\Solr\Backend;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

/**
 * Abstract factory for SOLR backends.
 *
 * @category VuFind2
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
abstract class AbstractSolrBackendFactory implements FactoryInterface
{
    /**
     * Logger.
     *
     * @var Zend\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Superior service manager.
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Search configuration file identifier.
     *
     * @var string
     */
    protected $searchConfig;

    /**
     * YAML searchspecs filename.
     *
     * @var string
     */
    protected $searchYaml;

    /**
     * VuFind configuration reader
     *
     * @var \VuFind\Config\PluginManager
     */
    protected $config;

    /**
     * Solr core name
     *
     * @var string
     */
    protected $solrCore = '';

    /**
     * Solr field used to store unique identifiers
     *
     * @var string
     */
    protected $uniqueKey = 'id';

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Create the backend.
     *
     * @param ServiceLocatorInterface $serviceLocator Superior service manager
     *
     * @return BackendInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        $this->config         = $this->serviceLocator->get('VuFind\Config');
        if ($this->serviceLocator->has('VuFind\Logger')) {
            $this->logger = $this->serviceLocator->get('VuFind\Logger');
        }
        $connector = $this->createConnector();
        $backend   = $this->createBackend($connector);
        $this->createListeners($backend);
        return $backend;
    }

    /**
     * Create the SOLR backend.
     *
     * @param Connector $connector Connector
     *
     * @return Backend
     */
    protected function createBackend(Connector $connector)
    {
        $backend = new Backend($connector);
        $backend->setQueryBuilder($this->createQueryBuilder());
        if ($this->logger) {
            $backend->setLogger($this->logger);
        }
        return $backend;
    }

    /**
     * Create listeners.
     *
     * @param Backend $backend Backend
     *
     * @return void
     */
    protected function createListeners(Backend $backend)
    {
        $events = $this->serviceLocator->get('SharedEventManager');

        // Highlighting
        $highlightListener = new InjectHighlightingListener($backend);
        $highlightListener->attach($events);

        // Spellcheck
        $config  = $this->config->get('config');
        if (isset($config->Spelling->enabled) && $config->Spelling->enabled) {
            if (isset($config->Spelling->simple) && $config->Spelling->simple) {
                $dictionaries = array('basicSpell');
            } else {
                $dictionaries = array('default', 'basicSpell');
            }
            $spellingListener = new InjectSpellingListener($backend, $dictionaries);
            $spellingListener->attach($events);
        }

        // Apply field stripping if applicable:
        $search = $this->config->get($this->searchConfig);
        if (isset($search->StripFields) && isset($search->IndexShards)) {
            $strip = $search->StripFields->toArray();
            foreach ($strip as $k => $v) {
                $strip[$k] = array_map('trim', explode(',', $v));
            }
            $mindexListener = new MultiIndexListener(
                $backend,
                $search->IndexShards->toArray(),
                $strip,
                $this->loadSpecs()
            );
            $mindexListener->attach($events);
        }

        // Attach error listeners for Solr 3.x and Solr 4.x (for backward
        // compatibility with VuFind 1.x instances).
        $legacyErrorListener = new LegacyErrorListener($backend);
        $legacyErrorListener->attach($events);
        $errorListener = new ErrorListener($backend);
        $errorListener->attach($events);
    }

    /**
     * Get the Solr core.
     *
     * @return string
     */
    protected function getSolrCore()
    {
        return $this->solrCore;
    }

    /**
     * Get the Solr URL.
     *
     * @return string
     */
    protected function getSolrUrl()
    {
        return $this->config->get('config')->Index->url . '/' . $this->getSolrCore();
    }

    /**
     * Create the SOLR connector.
     *
     * @return Connector
     */
    protected function createConnector()
    {
        $config = $this->config->get('config');
        $search = $this->config->get($this->searchConfig);

        $handlers = array(
            'select' => array(
                'fallback' => true,
                'defaults' => array('fl' => '*,score'),
                'appends'  => array('fq' => array()),
            ),
            'term' => array(
                'functions' => array('terms'),
            ),
        );

        // Hidden filters
        if (isset($search->HiddenFilters)) {
            foreach ($search->HiddenFilters as $field => $value) {
                array_push(
                    $handlers['select']['appends']['fq'],
                    sprintf('%s:"%s"', $field, $value)
                );
            }
        }

        // Raw hidden filters
        if (isset($search->RawHiddenFilters)) {
            foreach ($search->RawHiddenFilters as $filter) {
                array_push(
                    $handlers['select']['appends']['fq'],
                    $filter
                );
            }
        }

        $connector = new Connector(
            $this->getSolrUrl(), new HandlerMap($handlers), $this->uniqueKey
        );
        $connector->setTimeout(
            isset($config->Index->timeout) ? $config->Index->timeout : 30
        );

        if ($this->logger) {
            $connector->setLogger($this->logger);
        }
        if ($this->serviceLocator->has('VuFind\Http')) {
            $connector->setProxy($this->serviceLocator->get('VuFind\Http'));
        }
        return $connector;
    }

    /**
     * Create the query builder.
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilder()
    {
        $specs   = $this->loadSpecs();
        $config = $this->config->get('config');
        $defaultDismax = isset($config->Index->default_dismax_handler)
            ? $config->Index->default_dismax_handler : 'dismax';
        $builder = new QueryBuilder($specs, $defaultDismax);

        // Configure builder:
        $search = $this->config->get($this->searchConfig);
        $builder->caseSensitiveRanges
            = isset($search->General->case_sensitive_ranges)
            ? $search->General->case_sensitive_ranges : true;
        $builder->caseSensitiveBooleans
            = isset($search->General->case_sensitive_bools)
            ? $search->General->case_sensitive_bools : true;

        return $builder;
    }

    /**
     * Load the search specs.
     *
     * @return array
     */
    protected function loadSpecs()
    {
        return $this->serviceLocator->get('VuFind\SearchSpecsReader')
            ->get($this->searchYaml);
    }
}