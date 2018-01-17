<?php
/**
 * AuthorFacets aspect of the Search Multi-class (Results)
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Search_SolrAuthorFacets
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Bsz\Search\SolrAuthorFacets;

/**
 * AuthorFacets Search Results
 *
 * @category VuFind2
 * @package  Search_SolrAuthorFacets
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class Results extends \VuFind\Search\Solr\Results
{
    /**
     * Support method for performAndProcessSearch -- perform a search based on the
     * parameters passed to the object.
     *
     * @return void
     */
    protected function performSearch()
    {
        $query = $this->getParams()->getQuery();
        $params = $this->getParams()->getBackendParameters();
        // Perform the search:
        $collection = $this->getSearchService()
            ->search($this->backendId, $query, 0, 0, $params);

        $this->responseFacets = $collection->getFacets();

        // Get the facets from which we will build our results:
        $facets = $this->getFacetList(['author_facet' => null]);
        if (isset($facets['author_facet'])) {
            $params = $this->getParams();
            $this->resultTotal
                = (($params->getPage() - 1) * $params->getLimit())
                + count($facets['author_facet']['list']);
            $this->results = array_slice(
                $facets['author_facet']['list'], 0, $params->getLimit()
            );
        }
    }

    /**
     * Is the current search saved in the database?
     *
     * @return bool
     */
    public function isSavedSearch()
    {
        // Author searches are never saved:
        return false;
    }
    
        /**
     * Returns the stored list of facets for the last search
     *
     * @param array $filter Array of field => on-screen description listing
     * all of the desired facet fields; set to null to get all configured values.
     *
     * @return array        Facets data arrays
     */
    public function getFacetList($filter = null)
    {
        // Make sure we have processed the search before proceeding:
        if (null === $this->responseFacets) {
            $this->performAndProcessSearch();
        }

        // If there is no filter, we'll use all facets as the filter:
        if ($filter === null) {
            $filter = $this->getParams()->getFacetConfig();
        }

        // Start building the facet list:
        $list = [];

        // Loop through every field returned by the result set
        $fieldFacets = $this->responseFacets->getFieldFacets();
        foreach (array_keys($filter) as $field) {
            $data = isset($fieldFacets[$field]) ? $fieldFacets[$field] : [];
            // Skip empty arrays:
            if (count($data) < 1) {
                continue;
            }
            // Initialize the settings for the current field
            $list[$field] = [];
            // Add the on-screen label
            $list[$field]['label'] = $filter[$field];
            // Build our array of values for this field
            $list[$field]['list']  = [];
            // Should we translate values for the current facet?
            $translate
                = in_array($field, $this->getOptions()->getTranslatedFacets());
            // Loop through values:
            foreach ($data as $value => $count) {
                // Initialize the array of data about the current facet:
                $currentSettings = [];
                $currentSettings['value'] = $value;
                $currentSettings['displayText']
                    = $translate ? $this->translate($value) : $value;
                $currentSettings['count'] = $count;
                $currentSettings['operator']
                    = $this->getParams()->getFacetOperator($field);
                $currentSettings['isApplied']
                    = $this->getParams()->hasFilter("$field:" . $value)
                    || $this->getParams()->hasFilter("~$field:" . $value);

                // Store the collected values:
                $list[$field]['list'][] = $currentSettings;
            }
        }
        return $list;
    }
}