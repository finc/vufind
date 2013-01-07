<?php
/**
 * "Retrieve search options" view helper
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
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_recommendations_module Wiki
 */
namespace VuFind\View\Helper\Root;

/**
 * "Retrieve search options" view helper
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_recommendations_module Wiki
 */
class SearchOptions extends \Zend\View\Helper\AbstractHelper
{
    /**
     * Search manager
     *
     * @var \VuFind\Search\Manager
     */
    protected $manager;

    /**
     * Constructor
     *
     * @param \VuFind\Search\Manager $manager Search manager
     */
    public function __construct(\VuFind\Search\Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Wrapper to the search manager's getOptionsInstance method
     *
     * @param string $type The search type of the object to retrieve
     *
     * @return \VuFind\Search\Base\Options
     */
    public function __invoke($type = 'Solr')
    {
        return $this->manager->setSearchClassId($type)->getOptionsInstance();
    }
}