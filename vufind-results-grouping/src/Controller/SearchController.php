<?php
namespace VuFindResultsGrouping\Controller;

/**
 * This adds deduplication handling to VuFinds search controller
 *
 * Class SearchController
 * @package  VuFindResultsGrouping\Controller
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SearchController extends \VuFind\Controller\SearchController
{
    public function resultsAction()
    {
        $dedup = $this->serviceLocator->get('VuFindResultsGrouping\Config\Dedup');

        $view = Parent::resultsAction();
        $view->dedup = $dedup->isActive();
        return $view;
    }

    /**
     * Taken from AbstractSolrSearch class
     * Process the facets to be used as limits on the Advanced Search screen.
     *
     * @param array  $facetList          The advanced facet values
     * @param object $searchObject       Saved search object (false if none)
     * @param array  $hierarchicalFacets Hierarchical facet list (if any)
     * @param array  $hierarchicalFacetsSortOptions Hierarchical facet sort options
     * (if any)
     *
     * @return array               Sorted facets, with selected values flagged.
     */
    protected function processAdvancedFacets(
        $facetList,
        $searchObject = false,
        $hierarchicalFacets = [],
        $hierarchicalFacetSortOptions = []
    ) {
        // Process the facets
        $facetHelper = null;
        if (!empty($hierarchicalFacets)) {
            $facetHelper = $this->serviceLocator
                ->get(\VuFind\Search\Solr\HierarchicalFacetHelper::class);
        }
        foreach ($facetList as $facet => &$list) {
            // Hierarchical facets: format display texts and sort facets
            // to a flat array according to the hierarchy
            if (in_array($facet, $hierarchicalFacets)) {
                $tmpList = $list['list'];
                $facetHelper->sortFacetList($tmpList, true);
                $tmpList = $facetHelper->buildFacetArray(
                    $facet,
                    $tmpList
                );
                $list['list'] = $facetHelper->flattenFacetHierarchy($tmpList);
            }

            foreach ($list['list'] as $key => $value) {
                // Build the filter string for the URL:
                $fullFilter = ($value['operator'] == 'OR' ? '~' : '')
                    . $facet . ':"' . $value['value'] . '"';

                // If we haven't already found a selected facet and the current
                // facet has been applied to the search, we should store it as
                // the selected facet for the current control.
                if ($searchObject
                    && $searchObject->getParams()->hasFilter($fullFilter)
                ) {
                    $list['list'][$key]['selected'] = true;
                    // Remove the filter from the search object -- we don't want
                    // it to show up in the "applied filters" sidebar since it
                    // will already be accounted for by being selected in the
                    // filter select list!
                    $searchObject->getParams()->removeFilter($fullFilter);
                }
            }
        }
        return $facetList;
    }
}
