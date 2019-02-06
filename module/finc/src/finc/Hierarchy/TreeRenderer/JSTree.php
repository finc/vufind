<?php
/**
 * Hierarchy Tree Renderer for the JS_Tree plugin
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
 * @category VuFind
 * @package  HierarchyTree_Renderer
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace finc\Hierarchy\TreeRenderer;

/**
 * Hierarchy Tree Renderer
 *
 * This is a helper class for producing hierarchy trees.
 *
 * @category VuFind
 * @package  HierarchyTree_Renderer
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
class JSTree extends \VuFind\Hierarchy\TreeRenderer\JSTree
{
    /**
     * Constructor
     *
     * @param \Zend\Mvc\Controller\Plugin\Url $router Router plugin for urls
     */
    public function __construct(\Zend\Mvc\Controller\Plugin\Url $router)
    {
        parent::__construct($router);
    }

    /**
     * Use the router to build the appropriate URL based on context
     *
     * @param object $node    JSON object of a node/top node
     * @param string $context Record or Collection
     *
     * @return string
     */
    protected function getContextualUrl($node, $context)
    {
        if ($node->type == 'collection') {
            return $this->getUrlFromRouteCache('collection', $node->id);
        } else {
            $url = $this->getUrlFromRouteCache('record', $node->id);
            return $url;
        }
    }

    /**
     * Get the URL for a record and cache it to avoid the relatively slow routing
     * calls.
     *
     * @param string $route Route
     * @param string $id    Record ID
     *
     * @return string URL
     */
    protected function getUrlFromRouteCache($route, $id)
    {
        static $cache = [];
        if (!isset($cache[$route])) {
            if ($route == 'collection') {
                $params = [
                    'id' => '__record_id__',
                    'tab' => 'HierarchyTree'
                ];
                $options = [
                    'query' => [
                        'recordID' => '__record_id__'
                    ]
                ];
                $cache[$route] = $this->router->fromRoute($route, $params, $options);
            } else {
                $params = [
                    'id' => '__record_id__',
                    'tab' => 'Description'
                ];
                $cache[$route] = $this->router->fromRoute($route, $params);
            }
        }
        return str_replace('__record_id__', $id, $cache[$route]);
    }

    /**
     * @return \VuFind\Hierarchy\TreeDataSource\AbstractBase
     */
    public function getDataSource()
    {
        if (!isset($this->dataSource)) {
            $this->dataSource = parent::getDataSource();
        }
        $this->dataSource->setRecordDriver($this->recordDriver);
        return $this->dataSource;
    }
}
