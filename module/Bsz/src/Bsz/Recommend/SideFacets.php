<?php

/*
 * The MIT License
 *
 * Copyright 2016 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Bsz\Recommend;

use VuFind\Search\Solr\HierarchicalFacetHelper;

/**
 * BSDZ version of SideFacets to modify selected facets
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SideFacets extends \VuFind\Recommend\SideFacets
{
    /**
     * array of allowed facets. All other are thrown away
     * @var array
     */
    protected $filterFacets;

    /**
     * O
     * @var array
     */
    protected $addIsil;

    /**
     * Constructor
     *
     * @param \VuFind\Config\PluginManager $configLoader Configuration loader
     * @param HierarchicalFacetHelper      $facetHelper  Helper for handling
     * hierarchical facets
     */
    public function __construct(
    \VuFind\Config\PluginManager $configLoader, HierarchicalFacetHelper $facetHelper = null, $isil = null
    ) {
        parent::__construct($configLoader, $facetHelper);
        if (!empty($isil)) {
            $this->addIsil = $isil;
        }
    }

    /**
     * Get facet information from the search results.
     *
     * @return array
     * @throws \Exception
     */
    public function getFacetSet()
    {
        $facetSet = $this->results->getFacetList($this->mainFacets);
        foreach ($this->hierarchicalFacets as $hierarchicalFacet) {
            if (isset($facetSet[$hierarchicalFacet])) {
                if (!$this->hierarchicalFacetHelper) {
                    throw new \Exception(
                    get_class($this) . ': hierarchical facet helper unavailable'
                    );
                }

                $facetArray = $this->hierarchicalFacetHelper->buildFacetArray(
                        $hierarchicalFacet, $facetSet[$hierarchicalFacet]['list']
                );
                $facetSet[$hierarchicalFacet]['list'] = $this->hierarchicalFacetHelper
                        ->flattenFacetHierarchy($facetArray);
            }
        }
        return $this->filterFacetSet($facetSet);
    }

    /**
     * Filter out any unwanted facets
     * @param array $facetSet
     */
    public function filterFacetSet($facetSet)
    {
        if (isset($this->filterFacets)) {
            foreach ($this->filterFacets as $facet => $filter) {
                if (isset($facetSet[$facet])) {
                    $allowed = explode(',', $filter);
                    foreach ($facetSet[$facet]['list'] as $key => $originalFacet) {
                        if (!in_array($originalFacet['value'], $allowed)) {
                            //unset facet values we do not want
                            unset($facetSet[$facet]['list'][$key]);
                        }
                    }
                    if (count($facetSet[$facet]['list']) < 1) {
                        // No Facets remained - remove the facet
                        unset($facetSet[$facet]);
                    } else {
                        //Re-number the array
                        $facetSet[$facet]['list'] = array_values($facetSet[$facet]['list']);
                    }
                }
            }
        }
        return $facetSet;
    }

    /**
     * Add filter facets setting
     * @param type $settings
     */
    public function setConfig($settings)
    {
        parent::setConfig($settings);
        // Parse the additional settings:
        $settings = explode(':', $settings);
        $mainSection = empty($settings[0]) ? 'Results' : $settings[0];
        $checkboxSection = $settings[1] ?? false;
        $iniName = $settings[2] ?? 'facets';

        // Load the desired facet information...
        $config = $this->configLoader->get($iniName);

        if (isset($config->Filter_Facets)) {
            $this->filterFacets = $config->Filter_Facets;
        }
        if (!empty($this->addIsil)) {
            $this->filterFacets['institution_id'] = implode(',', $this->addIsil);
        }
    }
}
