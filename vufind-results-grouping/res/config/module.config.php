<?php
namespace VuFindResultsGrouping\Module\Configuration;

$config = [
    'service_manager' => [
        'factories' => [
            'VuFindResultsGrouping\Config\Grouping'  => 'VuFindResultsGrouping\Config\Factory::getGrouping',
        ],
    ],
    'controllers' => [
        'factories' => [
            'VuFindResultsGrouping\Controller\SearchController' => 'VuFind\Controller\AbstractBaseFactory',
        ],
        'aliases' => [
            'VuFind\Controller\SearchController'    => 'VuFindResultsGrouping\Controller\SearchController',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'ajaxhandler' => [
                'factories' => [
                    'VuFindResultsGrouping\AjaxHandler\GroupingCheckbox' =>
                        'VuFindResultsGrouping\AjaxHandler\GroupingCheckboxFactory',
                ],
                'aliases' => [
                    'groupingCheckbox' => 'VuFindResultsGrouping\AjaxHandler\GroupingCheckbox',
                ]
            ],
            'search_backend' => [
                'factories' => [
                    'Solr' => 'VuFindResultsGrouping\Search\Factory\SolrDefaultBackendFactory',
                ],
            ],
            'search_params'  => [
                'factories' => [
                    'VuFindResultsGrouping\Search\Solr\Params' => 'VuFindResultsGrouping\Search\Params\Factory::getSolr'
                ],
                'aliases' => [
                    'VuFind\Search\Solr\Params' => 'VuFindResultsGrouping\Search\Solr\Params'
                ]
            ],
        ],
        'template_injection' => [
            'VuFindResultsGrouping/'
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            '/usr/local/vufind/vendor/finc/vufind-results-grouping/res/theme/templates',
        ],
    ],
];
$dir = __DIR__;
return $config;
