<?php
/**
 * VuFind Configuration Manager Factory
 *
 * Copyright (C) 2018 Leipzig University Library <info@ub.uni-leipzig.de>
 *
 * PHP version 5.6
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
 * along with this program; if not, write to the Free Software Foundation,
 * Inc. 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @category VuFind
 * @package  Config
 * @author   Sebastian Kehr <kehr@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU GPLv2
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace VuFind\Config;

use Interop\Container\ContainerInterface;
/**
 * VuFind Configuration Manager
 *
 * @category VuFind
 * @package  Config
 * @author   Sebastian Kehr <kehr@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        if (!file_exists(Manager::CONFIG_CACHE_DIR)) {
            mkdir(Manager::CONFIG_CACHE_DIR, 0700);
        }

        Manager::init();
        $manager = new Manager;

        if (!Manager::CACHE_ENABLED) {
            $manager->reset();
        }

        return $manager;
    }
}
