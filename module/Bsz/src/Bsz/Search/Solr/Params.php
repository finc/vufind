<?php

namespace Bsz\Search\Solr;

/**
 * Description of Params
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Params extends \VuFind\Search\Solr\Params
{
        /**
     * Return the current filters as an array of strings ['field:filter']
     *
     * @return array $filterQuery
     */
    public function getFilterSettings()
    {
        // Define Filter Query
        $filterQuery = [];
        $orFilters = [];
        $filterList = array_merge(
            $this->getHiddenFilters(),
            $this->filterList
        );
        foreach ($filterList as $field => $filter) {
            if ($orFacet = (substr($field, 0, 1) == '~')) {
                $field = substr($field, 1);
            }
            foreach ($filter as $value) {
                // Special case -- complex filter, that should be taken as-is:
                if ($field == '#') {
                    $q = $value;
                } elseif (substr($value, -1) == '*'
                    || preg_match('/\[[^\]]+\s+TO\s+[^\]]+\]/', $value)
                ) {
                    // Special case -- allow trailing wildcards and ranges
                    $q = $field . ':' . $value;
                } else {
                    $q = $field . ':"' . addcslashes($value, '"\\') . '"';
                }
                if ($orFacet) {
                    $orFilters[$field] = isset($orFilters[$field])
                        ? $orFilters[$field] : [];
                    $orFilters[$field][] = $q;
                } else {
                    $filterQuery[] = $q;
                }
            }
        }
        foreach ($orFilters as $field => $parts) {
            $filterQuery[] = '{!tag=' . $field . '_filter}' . $field
                . ':(' . implode(' OR ', $parts) . ')';
        }
        return $filterQuery;
    }
    
        /**
     * Get hidden filters grouped by field like normal filters.
     *
     * @return array
     */
    public function getHiddenFilters()
    {
        $hidden = parent::getHiddenFilters();
        $config = $this->configLoader->get('config');
        $isils_string = $config->get('Site')->get('isil');
        $isils = explode(',', $isils_string);
        foreach ($isils as $isil) {
            if (Array_key_exists('institution_id', $hidden) 
                && !in_array($isil, $hidden['institution_id'])) {                
                array_push($hidden['institution_id'], $isil);
            }
        }
        return $hidden;
    }
}
