<?php
namespace BszGrouping\Module\Configuration;

$config = [
    'service_manager' => [
        'factories' => [
            'BszGrouping\Config\Dedup'  => 'BszGrouping\Config\Factory::getDedup',
        ],
    ],
    'controllers' => [
        'factories' => [
            'BszGrouping\Controller\SearchController' => 'VuFind\Controller\AbstractBaseFactory',
        ],
        'aliases' => [
            'VuFind\Controller\SearchController'    => 'BszGrouping\Controller\SearchController',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'ajaxhandler' => [
                'factories' => [
                    'BszGrouping\AjaxHandler\DedupCheckbox' =>
                        'BszGrouping\AjaxHandler\DedupCheckboxFactory',
                ],
                'aliases' => [
                    'dedupCheckbox' => 'BszGrouping\AjaxHandler\DedupCheckbox',
                ]
            ],
            'search_backend' => [
                'factories' => [
                    'Solr' => 'BszGrouping\Search\Factory\SolrDefaultBackendFactory',
                ],
            ],
            'search_params'  => [
                'factories' => [
                    'BszGrouping\Search\Solr\Params' => 'BszGrouping\Search\Params\Factory::getSolr'
                ],
                'aliases' => [
                    'VuFind\Search\Solr\Params' => 'BszGrouping\Search\Solr\Params'
                ]
            ],
        ],
        'template_injection' => [
            'BszGrouping/'
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            '/usr/local/vufind/vendor/finc/bsz-grouping/res/theme/templates',
        ],
    ],
];
$dir = __DIR__;
return $config;
