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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Config
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace finc\Config;
use Symfony\Component\Yaml\Yaml;

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
class BranchesReader
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
            // Connect to branches cache:
            $cache = (null !== $this->cacheManager)
                ? $this->cacheManager->getCache('branches') : false;

            // Determine full configuration file path:
            $fullpath = \VuFind\Config\Locator::getBaseConfigPath($filename);
            $local = \VuFind\Config\Locator::getLocalConfigPath($filename);

            // Generate cache key:
            $cacheKey = $filename . '-'
                . (file_exists($fullpath) ? filemtime($fullpath) : 0);
            if (!empty($local)) {
                $cacheKey .= '-local-' . filemtime($local);
            }
            $cacheKey = md5($cacheKey);

            // Generate data if not found in cache:
            if ($cache === false || !($results = $cache->getItem($cacheKey))) {
                $results = file_exists($fullpath)
                    ? Yaml::parse(file_get_contents($fullpath)) : [];
                if (!empty($local)) {
                    $localResults = Yaml::parse(file_get_contents($local));
                    foreach ($localResults as $key => $value) {
                        $results[$key] = $value;
                    }
                }
                if ($cache !== false) {
                    $cache->setItem($cacheKey, $results);
                }
            }
            $this->branches[$filename] = $results;
        }

        return $this->branches[$filename];
    }
}
