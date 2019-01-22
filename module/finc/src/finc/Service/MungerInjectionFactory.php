<?php

namespace finc\Service;

use VuFindSearch\Query\Query,
    VuFindSearch\Query\QueryGroup;
use Zend\Config\Config;
use Zend\ServiceManager\DelegatorFactoryInterface,
    Zend\ServiceManager\ServiceLocatorInterface;
use Zend\EventManager\EventInterface;
use VuFindSearch\Service as SearchService;

/**
 * Class MungerInjectionFactory
 * A Delegator Factory that registers several listeners at events triggered by the VuFind\Search service.
 * @package finc\Service
 */
class MungerInjectionFactory implements DelegatorFactoryInterface {

    /**
     * @var SearchService
     */
    protected $instance;

    /**
     * @var array names of search handlers for which colons should be escaped
     */
    protected $searches_to_escape;

    /**
     * @var array shard configuration to register in all queries
     */
    protected $shards_to_register;

    /**
     * Creates a delegator of VuFind/Search to register several listeners.
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @param callable $callback
     * @return mixed
     */
    public function createDelegatorWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName, $callback)
    {
        $instance = call_user_func($callback);
        $searchConfig = $serviceLocator->get('VuFind\Config')->get('searches');
        $e = $instance->getEventManager()->getSharedManager();
        $handlers = $searchConfig->General->escaped_colon_searches;
        if (!empty($handlers)) {
            $this->searches_to_escape = $handlers->toArray();
            $e->attach('VuFind\Search', 'pre',
                function (EventInterface $event) {
                    $params = $event->getParams();
                    if (isset($params['query'])) {
                        $params['query'] = $this->escapeColons($params['query']);
                    }
                }
            );
        }
        $shards = $searchConfig->IndexShards->toArray();
        if ($excludedShards = $searchConfig->ShardPreferences->on_user_search_only) {
            $shards = array_diff_key($shards, array_flip(explode(',', $excludedShards)));
        }
        if (!empty($shards)) {
            $this->shards_to_register = $shards;
            $e->attach('VuFind\Search', 'pre', [$this, 'registerShards']);
        }
        return $instance;
    }

    /**
     * Escapes colons in Queries or recursively in QueryGroups.
     * This prevents queries from being interpreted as advanced queries in Lucene syntax.
     * cf. \VuFindSearch\Backend\Solr\LuceneSyntaxHelper::containsAdvancedLuceneSyntax
     * @param Query|QueryGroup $queryOrGroup
     * @return mixed
     */
    private function escapeColons($queryOrGroup) {

        if ($queryOrGroup instanceof QueryGroup) {
            $handler = $queryOrGroup->getReducedHandler();
            if (is_null($handler) || in_array($handler,$this->searches_to_escape)) {
                foreach ($queryOrGroup->getQueries() as $query) {
                    $this->escapeColons($query);
                }
            }
        } elseif (in_array($queryOrGroup->getHandler(),$this->searches_to_escape)) {
            $queryOrGroup->setString(
            // mask whitespaces that follow a colon
            // that avoids the removal of that very colon via
            // \VuFindSearch\Backend\Solr\LuceneSyntaxHelper::normalizeColons
                preg_replace('/(?<=\:)\s/', '\ ', $queryOrGroup->getString())
            );
        }
        return $queryOrGroup;
    }

    /**
     * Event Listener on Search/Pre that registers all configured shards for every search request
     * @param EventInterface $event
     */
    public function registerShards(EventInterface $event) {

        $params = $event->getParam('params');
        if (empty($params->get('shards'))) {
            $params->set('shards',implode(',',$this->shards_to_register));
        }
    }
}