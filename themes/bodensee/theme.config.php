<?php
$config = [
    'extends' => 'bootstrap3',
    // client specific CSS files are added in ThemeInfo.php
      'favicon' => '/themes/bodensee/images/favicon/default.ico',
    'js' => [
        'additions.js'
    ],     
    'helpers' => [
        'factories' => [
//            'flashmessages' => 'VuFind\View\Helper\Bootstrap3\Factory::getFlashmessages',
            'layoutclass' => 'BszTheme\View\Helper\Bodensee\Factory::getLayoutClass',
            'openurl' => 'BszTheme\View\Helper\Bodensee\Factory::getOpenUrl',
//            'searchtabs' => 'VuFind\View\Helper\Bodensee\Factory::getSearchTabs',
//            'record' => 'VuFind\View\Helper\Bodensee\Factory::getRecord',
//            'client' => 'Bsz\View\Helper\Factory::getClient',
//            'libraries' => 'Bsz\View\Helper\Factory::getLibraries',
//            'recordLink' => 'BszTheme\View\Helper\Bodensee\Factory::getRecordLink',
            'getLastSearchLink' => 'BszTheme\View\Helper\Bodensee\Factory::getGetLastSearchLink',
//            'illform' => 'Bsz\View\Helper\Factory::getIllForm',
//            'piwik' => 'BszTheme\View\Helper\Bodensee\Factory::getPiwik',
        ],
        'invokables' => [
//            'highlight' => 'VuFind\View\Helper\Bootstrap3\Highlight',
//            'search' => 'VuFind\View\Helper\Bootstrap3\Search',
//            'vudl' => 'VuDL\View\Helper\Bootstrap3\VuDL',
//            'mapper' => 'Bsz\View\Helper\FormatMapper',
//            'string' => 'BszTheme\View\Helper\StringHelper',
        ],
    ]
];
return $config;
