<?php 
$config = [
    'extends' => 'bootstrap3',
      'favicon' => '/themes/bodensee/images/favicon/default.ico',
    'js' => [
        'additions.js',
        'vendor/jquery.mark.min.js',
        'vendor/clipboard.min.js'
    ],     
    'helpers' => [
        'factories' => [
            'Bsz\View\Helper\Bootstrap3\LayoutClass' => 'BszTheme\View\Helper\Bodensee\Factory::getLayoutClass',
            'Bsz\View\Helper\Root\OpenUrl' => 'BszTheme\View\Helper\Bodensee\Factory::getOpenUrl',
            'Bsz\View\Helper\Root\Record' => 'BszTheme\View\Helper\Bodensee\Factory::getRecord',
            'Bsz\View\Helper\Root\RecordLink' => 'BszTheme\View\Helper\Bodensee\Factory::getRecordLink',
            'Bsz\View\Helper\Root\Piwik' => 'BszTheme\View\Helper\Bodensee\Factory::getPiwik',
            'Bsz\View\Helper\Root\SearchTabs' => 'BszTheme\View\Helper\Bodensee\Factory::getSearchTabs',
            'Bsz\View\Helper\Root\SearchMemory' => 'BszTheme\View\Helper\Bodensee\Factory::getSearchMemory',
            'illform' => 'BszTheme\View\Helper\Bodensee\Factory::getIllForm',
        ],
        'aliases' => [
            'VuFind\View\Helper\Bootstrap3\LayoutClass' => 'Bsz\View\Helper\Bootstrap3\LayoutClass',
            'VuFind\View\Helper\Root\OpenUrl' => 'Bsz\View\Helper\Root\OpenUrl',
            'VuFind\View\Helper\Root\Record' => 'Bsz\View\Helper\Root\Record',
            'VuFind\View\Helper\Root\RecordLink' => 'Bsz\View\Helper\Root\RecordLink',
            'VuFind\View\Helper\Root\Piwik' => 'Bsz\View\Helper\Root\Piwik',
            'VuFind\View\Helper\Root\SearchTabs' => 'Bsz\View\Helper\Root\SearchTabs',
            'VuFind\View\Helper\Root\SearchMemory' => 'Bsz\View\Helper\Root\SearchMemory'
        ]
    ]
];
return $config;
