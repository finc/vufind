<?php
namespace VuFindResultsGrouping\Module\Configuration;

$config = [
    'service_manager' => [
        'factories' => [
            'VuFindResultsGrouping\Config\Dedup'  => 'VuFindResultsGrouping\Config\Factory::getDedup',
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
                    'VuFindResultsGrouping\AjaxHandler\DedupCheckbox' =>
                        'VuFindResultsGrouping\AjaxHandler\DedupCheckboxFactory',
                ],
                'aliases' => [
                    'dedupCheckbox' => 'VuFindResultsGrouping\AjaxHandler\DedupCheckbox',
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
