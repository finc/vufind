<?php
/**
 * SOLR backend.
 *
 * @category Ida
 * @package  Search
 * @author   <dku@outermedia.de>
 */
namespace Bsz\Backend\Solr;

use VuFindSearch\ParamBag;
use VuFindSearch\Query\AbstractQuery;
use VuFindSearch\Response\RecordCollectionInterface;

class Backend extends \VuFindSearch\Backend\Solr\Backend
{
    /**
     * Perform a search and return record collection.
     *
     * @param AbstractQuery $query  Search query
     * @param integer       $offset Search offset
     * @param integer       $limit  Search limit
     * @param ParamBag      $params Search backend parameters
     *
     * @return RecordCollectionInterface
     */
    public function search(AbstractQuery $query, $offset, $limit,
                           ParamBag $params = null
    ) {
        $params = $params ?: new ParamBag();
        $this->injectResponseWriter($params);

        $params->set('rows', $limit);
        $params->set('start', $offset);
        $params->mergeWith($this->getQueryBuilder()->build($query));

        // Search in Volumes and Articles Tab without grouping
        // see limit param in Volumes.php and Articles.php
        if ($limit === 1000) {
            $params->set('group', 'false');
        }

        // Only group in ILL-TAB,
        if (!$params->contains('fq', '-consortium:"FL"')) {
            $params->set('group', 'false');
        }

        // Extended Search form without grouping
        // todo, was ist wenn andere facetten konfiguriert sind?
        if ($params->contains('facet.field', 'material_access') &&
            $params->contains('facet.field', 'material_content_type') &&
            $params->contains('q', '*:*')) {
            $params->set('group', 'false');
        }

        // Fetch results grouped
        if ($params->contains('group', 'true')) {
            $params->set('group', 'true');
            // Set defaults unless overridden:
            if ($params->contains('group.field', '')) {
                $params->set('group.field', 'test_matchkey_2');
            }
            if ($params->contains('group.limit', '')) {
                $params->set('group.limit', '10');
            }
            // ngroups have massive performance penalty!
            $params->set('group.ngroups', 'false');
            $params->set('stats', 'true');
            $params->set('stats.field', '{!cardinality=true}' . $params->get('group.field')['0']);
        }

        $response   = $this->connector->search($params);
        $collection = $this->createRecordCollection($response);
        $this->injectSourceIdentifier($collection);

        return $collection;
    }
}
