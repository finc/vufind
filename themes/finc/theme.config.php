<?php
return array(
    'extends' => 'bootstrap3',
    'js' => array(
        'openurl.js',
        'check_item_statuses.js',
        // remove nxt line when aria-hidden issue fixed, obsolete in VF5, see #12684
        'finc.js',
    ),
    'helpers' => array(
        'factories' => array(
            //'permission' => 'finc\View\Helper\Root\Factory::getPermission',
            'record' => 'finc\View\Helper\Root\Factory::getRecord',
            'recordlink' => 'finc\View\Helper\Root\Factory::getRecordLink',
            'interlibraryloan' =>
                'finc\View\Helper\Root\Factory::getInterlibraryLoanLink',
            'citation' => 'finc\View\Helper\Root\Factory::getCitation',
            'openurl' => 'finc\View\Helper\Root\Factory::getOpenUrl',
            'branchinfo' => 'finc\View\Helper\Root\Factory::getBranchInfo',
            'sidefacet' => 'finc\View\Helper\Root\Factory::getSideFacet',
            'externalCatalogueLink' =>
                'finc\View\Helper\Root\Factory::getExternalCatalogueLink',
            'recordDataFormatter' =>
                'finc\View\Helper\Root\RecordDataFormatterFactory',
        ),
        'invokables' => array(
            'resultfeed' => 'finc\View\Helper\Root\ResultFeed'
        )
    ),
);
