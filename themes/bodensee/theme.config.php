<?php 
$config = [
    'extends' => 'bootstrap3',
      'favicon' => '/themes/bodensee/images/favicon/default.ico',
    'js' => [
        'additions.js',
        'vendor/jquery.mark.min.js',
    ],     
    'helpers' => [
        'factories' => [
            'VuFind\View\Helper\Bootstrap3\LayoutClass' => 'BszTheme\View\Helper\Bodensee\Factory::getLayoutClass',
            'VuFind\View\Helper\Root\OpenUrl' => 'BszTheme\View\Helper\Bodensee\Factory::getOpenUrl',
            'VuFind\View\Helper\Root\Record' => 'BszTheme\View\Helper\Bodensee\Factory::getRecord',
            'VuFind\View\Helper\Root\RecordLink' => 'BszTheme\View\Helper\Bodensee\Factory::getRecordLink',
            'VuFind\View\Helper\Root\Piwik' => 'BszTheme\View\Helper\Bodensee\Factory::getPiwik',
            'VuFind\View\Helper\Root\SearchTabs' => 'BszTheme\View\Helper\Bodensee\Factory::getSearchTabs',
        ],
    ]
];
return $config;
