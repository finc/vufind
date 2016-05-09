<?php
/**
 * VuFind Cache Manager
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2007.
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
 * @package  Cache
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace finc\Cache;
use Zend\Config\Config;

/**
 * VuFind Cache Manager
 *
 * Creates file and APC caches
 *
 * @category VuFind
 * @package  Cache
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Manager extends \VuFind\Cache\Manager
{
    /**
     * Constructor
     *
     * @param Config $config       Main VuFind configuration
     * @param Config $searchConfig Search configuration
     */
    public function __construct(Config $config, Config $searchConfig)
    {
        parent::__construct($config, $searchConfig);

        // Get base cache directory.
        $cacheBase = $this->getCacheDir();

        // Set up branches cache based on config settings:
        $searchCacheType = isset($searchConfig->Cache->type)
            ? $searchConfig->Cache->type : false;
        switch ($searchCacheType) {
        case 'APC':
            $this->createAPCCache('branches');
            break;
        case 'File':
            $this->createFileCache(
              'branches', $cacheBase . 'branches'
            );
            break;
        case false:
            $this->createNoCache('branches');
            break;
        }
    }
}
