<?php
return array(
    'extends' => 'foundation5',
    'js' => array(
        'openurl.js',
        'check_item_statuses.js'
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
        ),
        'invokables' => array(
            'resultfeed' => 'finc\View\Helper\Root\ResultFeed'
        )
    ),
);
