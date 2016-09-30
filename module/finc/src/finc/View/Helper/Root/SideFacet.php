<?php
/**
 * Side facet view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\View\Helper\Root;

/**
 * Permissions view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class SideFacet extends \Zend\View\Helper\AbstractHelper
{
    /**
     * VuFind configuration
     *
     * @var \Zend\Config\Config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $config VuFind configuration
     * @access public
     */
    public function __construct($config = null)
    {
        $this->config = $config;
    }

    /**
     * Checks whether the user is logged in.
     *
     * @param array $sideFacets List of side facets
     *
     * @return array            Filtered side facets
     * @access public
     */
    public function displayAllowedFacetValues($sideFacets)
    {
        if (!isset($this->config->AllowFacetValue) ||
            count($this->config->AllowFacetValue) == 0
        ) {
            return $sideFacets;
        }
        foreach ($this->config->AllowFacetValue as $label => $values) {
            if (isset($sideFacets[$label])) {
                foreach ($sideFacets[$label]['list'] as $key => &$item) {
                    if (!in_array($item['value'], $values->toArray())) {
                        unset($sideFacets[$label]['list'][$key]);
                    }
                }
            }
        }
        return $sideFacets;
    }
}
