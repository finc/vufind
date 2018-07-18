<?php

/**
 * Factory for the default SOLR backend.
 *
 * @category Ida
 * @package  Search
 * @author   <dku@outermedia.de>
 */
namespace Bsz\Search\Factory;

use VuFindSearch\Backend\BackendInterface;
use Bsz\Backend\EDS\Backend;
use VuFindSearch\Backend\EDS\QueryBuilder;
use VuFindSearch\Backend\EDS\Response\RecordCollectionFactory;
use VuFindSearch\Backend\EDS\Zend2 as Connector;

class EdsBackendFactory extends \VuFind\Search\Factory\EdsBackendFactory {

        /**
     * Create the EDS backend.
     *
     * @param Connector $connector Connector
     *
     * @return Backend
     */
    protected function createBackend(Connector $connector)
    {
        $auth = $this->serviceLocator->get('ZfcRbac\Service\AuthorizationService');
        $isGuest = !$auth->isGranted('access.EDSExtendedResults');
        $session = new \Zend\Session\Container(
            'EBSCO', $this->serviceLocator->get('VuFind\SessionManager')
        );
        $backend = new Backend(
            $connector, $this->createRecordCollectionFactory(),
            $this->serviceLocator->get('VuFind\CacheManager')->getCache('object'),
            $session, $this->edsConfig, $isGuest
        );
        $backend->setAuthManager($this->serviceLocator->get('VuFind\AuthManager'));
        $backend->setLogger($this->logger);
        $backend->setQueryBuilder($this->createQueryBuilder());
        return $backend;
    }
}