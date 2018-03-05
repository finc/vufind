<?php
/**
 * VuFind configuration aggregation
 *
 * Copyright (C) 2010 Villanova University,
 *               2018 Leipzig University <info.ub.uni-leipzig.de>
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
 * @package  VuFindConfig
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Sebastian Kehr <kehr@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU GPLv2
 * @link     https://vufind.org/wiki/development Wiki
 */

// see https://docs.zendframework.com/zend-component-installer/
// for why config aggregation should take place within this file

use VuFind\Config\Provider;
use Zend\ConfigAggregator\ArrayProvider;
use Zend\ConfigAggregator\ConfigAggregator;

return function ()
{
    $useCache = APPLICATION_ENV != 'development'
        && !defined('VUFIND_PHPUNIT_RUNNING');

    $basePattern = "{" . APPLICATION_PATH . "," . LOCAL_OVERRIDE_DIR . "}/config/vufind/";
    $filePattern = "{,*/}*.{ini,json,yaml,php}";

    $aggregator = new ConfigAggregator([
        new ArrayProvider([ConfigAggregator::ENABLE_CACHE => $useCache]),
        new Provider($basePattern, $filePattern)
    ], LOCAL_CACHE_DIR . '/config.php');

    return $aggregator->getMergedConfig();
};