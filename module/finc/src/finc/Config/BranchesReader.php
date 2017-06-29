<?php
/**
 * VuFind Branches.yaml Configuration Reader
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
 * @package  Config
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace finc\Config;
use VuFind\Config\Locator as Locator,
    Symfony\Component\Yaml\Yaml;

/**
 * VuFind Branches.yaml Configuration Reader
 *
 * @category VuFind
 * @package  Config
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class BranchesReader extends \VuFind\Config\SearchSpecsReader
{
    /**
     * Cache manager
     *
     * @var \finc\Cache\Manager
     */
    protected $cacheManager;

    /**
     * Cache of loaded branches
     *
     * @var array
     */
    protected $branches = [];

    /**
     * Constructor
     *
     * @param \finc\Cache\Manager $cacheManager Cache manager (optional)
     */
    public function __construct(\finc\Cache\Manager $cacheManager = null)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Return branches
     *
     * @param string $filename config file name
     *
     * @return array
     */
    public function get($filename)
    {
        // Load data if it is not already in the object's cache:
        if (!isset($this->branches[$filename])) {
            $this->branches[$filename] = $this->getFromPaths(
                Locator::getBaseConfigPath($filename),
                Locator::getLocalConfigPath($filename)
            );
        }

        return $this->branches[$filename];
    }

    /**
     * Given core and local filenames, retrieve the searchspecs data.
     *
     * @param string $defaultFile Full path to file containing default YAML
     * @param string $customFile  Full path to file containing local customizations
     * (may be null if no local file exists).
     *
     * @return array
     */
    protected function getFromPaths($defaultFile, $customFile = null)
    {
        // Connect to searchspecs cache:
        $cache = (null !== $this->cacheManager)
            ? $this->cacheManager->getCache('branches') : false;

        // Generate cache key:
        $cacheKey = basename($defaultFile) . '-'
            . (file_exists($defaultFile) ? filemtime($defaultFile) : 0);
        if (!empty($customFile)) {
            $cacheKey .= '-local-' . filemtime($customFile);
        }
        $cacheKey = md5($cacheKey);

        // Generate data if not found in cache:
        if ($cache === false || !($results = $cache->getItem($cacheKey))) {
            $results = $this->parseYaml($customFile, $defaultFile);
            if ($cache !== false) {
                $cache->setItem($cacheKey, $results);
            }
        }

        return $results;
    }



}
