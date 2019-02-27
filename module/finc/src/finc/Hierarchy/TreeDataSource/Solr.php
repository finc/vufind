<?php
/**
 * Hierarchy Tree Data Source (Solr)
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
 * @package  HierarchyTree_DataSource
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace finc\Hierarchy\TreeDataSource;
use VuFind\Hierarchy\TreeDataSource\Solr as VuFindBase;
use VuFindSearch\Query\Query;
use VuFindSearch\Backend\Solr\Connector;
use VuFindSearch\ParamBag;
use VuFind\RecordDriver\AbstractBase as RecordDriver;

/**
 * Hierarchy Tree Data Source (Solr)
 *
 * This extends the base helper class to enable additional configuration of the answer set.
 *
 * @category VuFind
 * @package  HierarchyTree_DataSource
 * @author   Dorian Merz <merz@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
class Solr extends VuFindBase
{
    /**
     * @var RecordDriver
     */
    protected $recordDriver;

    /**
     * Search Solr.
     *
     * @param string $q    Search query
     * @param int    $rows Max rows to retrieve (default = int max / 2 since Solr
     * may choke with higher values)
     *
     * @return array
     */
    protected function searchSolr($q, $rows = 1073741823)
    {
        $filters = $this->filters;
        if ($this->isTopElementQuery($q) || (isset($this->recordDriver) && $this->recordDriver->isCollection())) {
            $filters[] = "format:DigitalCollection";
        }
        $params = new ParamBag(
            [
                'q'  => [$q],
                'fq' => $filters,
                'hl' => ['false'],
                'fl' => ['title,id,hierarchy_parent_id,hierarchy_top_id,'
                    . 'is_hierarchy_id,hierarchy_sequence,title_in_hierarchy,recordtype'],
                'wt' => ['json'],
                'json.nl' => ['arrarr'],
                'rows' => [$rows], // Integer max
                'start' => [0]
            ]
        );
        $response = $this->solrConnector->search($params);
        return json_decode($response);
    }

    public function setRecordDriver(RecordDriver $recordDriver) {
        $this->recordDriver = $recordDriver;
    }

    protected function isTopElementQuery($query) {
        if (preg_match('/^hierarchy_top_id\:\"?([^\"]+)\"?$/',$query,$matches)) {
            $id = $matches[1];
            $params = new ParamBag(
                [
                    'q' => ["hierarchy_top_id:\"$id\" AND id:\"$id\" AND format:DigitalCollection"],
                    'hl' => ['false'],
                    'fl' => ['id'],
                    'wt' => ['json'],
                    'json.nl' => ['arrarr'],
                    'rows' => [1], // Integer max
                    'start' => [0]
                ]
            );
            $response = $this->solrConnector->search($params);
            $return = json_decode($response);
            return $return->response->numFound != 0;
        }
        return false;
    }
}
