<?php
/**
 * Hierarchy Tree Data Formatter (JSON)
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2015.
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
 * @package  HierarchyTree_DataFormatter
 * @author   Dorian Merz <merz@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace finc\Hierarchy\TreeDataFormatter;

/**
 * Hierarchy Tree Data Formatter (JSON)
 *
 * @category VuFind
 * @package  HierarchyTree_DataFormatter
 * @author   Dorian Merz <merz@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
class NoCollections extends \VuFind\Hierarchy\TreeDataFormatter\Json
{
    protected function isCollection($fields)
    {
        //always return false to ensure Record-Links only
        return false;
    }
}
