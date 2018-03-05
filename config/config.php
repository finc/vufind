<?php

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