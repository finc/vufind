<?php
/**
 * Record link view helper
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
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\View\Helper\Root;
use VuFindSearch\Query\Query as Query,
    VuFind\Record\Loader as Loader,
    VuFind\Record\Router as Router,
    VuFindSearch\Service as SearchService;

/**
 * Record link view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class RecordLink extends \VuFind\View\Helper\Root\RecordLink
{
    /**
     * Record router
     *
     * @var \VuFind\Record\Router
     */
    protected $router;

    /**
     * Record loader
     *
     * @var \VuFind\Record\Loader
     */
    protected $recordLoader;

    /**
     * Connection used when searching for fincid
     *
     * @var VuFindSearch\Service
     */
    protected $searchService;

    /**
     * Constructor
     *
     * @param \VuFind\Record\Router $router Record router
     * @param \VuFind\Record\Loader $loader Record loader
     * @param \VuFindSearch\Service $ss     Search service
     */
    public function __construct(Router $router, Loader $loader, SearchService $ss)
    {
        $this->router = $router;
        $this->recordLoader = $loader;
        $this->searchService = $ss;
    }

    /**
     * Get the link to the record which is identified by $id in the Solr field $type.
     * If multiple records are found the best guess is to return the URL to the first
     * one. If none is found return null.
     *
     * @param string $id   Id identifying a specific record
     * @param string $type Solr field to be searched, defaults to null (searching in
     * any field)
     *
     * @return null|string Link to the found record, otherwise null
     */
    public function getRecordLink($id, $type = null)
    {
        try {
            $query = $type . ':' . $id;
            $result = $this->searchService->search('VuFind', new Query($query));
            if (count($result) === 0) {
                throw new \Exception(
                    'Problem retrieving record with ' . $type . ":" . $id
                );
            }
            return $this->getUrl(current($result->getRecords()));
        } catch (\Exception $e) {
            // logging etc won't help here, so do nothing
        }
        return null;
    }
}
