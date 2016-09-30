<?php
return array(
    'extends' => 'foundation5',
    'js' => array(
        'foundation.min.js',
        'openurl.js',
        'check_item_statuses.js',
        'record.js',
        'finc.js'
    ),
    'helpers' => array(
        'factories' => array(
            'permission' => 'finc\View\Helper\Root\Factory::getPermission',
            'record' => 'finc\View\Helper\Root\Factory::getRecord',
            'interlibraryloan' =>
                'finc\View\Helper\Root\Factory::getInterlibraryLoanLink',
            'citation' => 'finc\View\Helper\Root\Factory::getCitation',
            'openurl' => 'finc\View\Helper\Root\Factory::getOpenUrl',
            'branchinfo' => 'finc\View\Helper\Root\Factory::getBranchInfo',
            'sidefacet' => 'finc\View\Helper\Root\Factory::getSideFacet'
        ),
        'invokables' => array(
            'resultfeed' => 'finc\View\Helper\Root\ResultFeed'
        )
    ),
);
