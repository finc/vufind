<?php
/**
 * HierarchyTree tab
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  RecordTabs
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */
namespace finc\RecordTab;

/**
 * HierarchyTree tab
 *
 * @category VuFind
 * @package  RecordTabs
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */
class HierarchyTree extends \VuFind\RecordTab\HierarchyTree
{
    /**
     * Tree data
     *
     * @var array
     */
    protected $treeList = null;

    /**
     * Configuration
     *
     * @var \Zend\Config\Config
     */
    protected $config = null;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $config Configuration
     */
    public function __construct(\Zend\Config\Config $config)
    {
        $this->config = $config;
    }

    public function getDescription()
    {
        if ($this->driver->isCollection()) return parent::getDescription();
        else return 'From same Collection';
    }

    /**
     * Is this tab active?
     *
     * @return bool
     */
/*    public function isActive()
    {
        return (
            $this->getRecordDriver()->tryMethod('isSingleElementHierarchyRecord')
                ? false : parent::isActive()
        );
    }
*/
}
