<?php
namespace finc\Module\Configuration;

$config = [
    'service_manager' => [
        'factories' => [
            'VuFind\Mailer' => 'finc\Mailer\Factory'
        ]
    ],
    'controllers' => [
        'factories' => [
            'record' => 'finc\Controller\Factory::getRecordController'
        ],
        'invokables' => [
            'my-research' => 'finc\Controller\MyResearchController'
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'ils_driver' => [
                'factories' => [
                    'fincils' => 'finc\ILS\Driver\Factory::getFincILS',
                    'daia' => 'finc\ILS\Driver\Factory::getDAIA',
                    'paia' => 'finc\ILS\Driver\Factory::getPAIA',
                ],
            ],
            'recorddriver' => [
                'factories' => [
                    'solrdefault' => 'finc\RecordDriver\Factory::getSolrDefault',
                    'solrmarc' => 'finc\RecordDriver\Factory::getSolrMarc',
                    'solrmarcfinc' => 'finc\RecordDriver\Factory::getSolrMarcFinc',
                    'solrmarcfincpda' => 'finc\RecordDriver\Factory::getSolrMarcFincPDA',
                    'solrmarcremote' => 'finc\RecordDriver\Factory::getSolrMarcRemote',
                    'solrmarcremotefinc' => 'finc\RecordDriver\Factory::getSolrMarcRemoteFinc',
                    'solrai' => 'finc\RecordDriver\Factory::getSolrAI',
                ],
            ],
            'recordtab' => [
                'invokables' => [
                    'additional' => 'finc\RecordTab\Additional',
                    'staffviewai' => 'finc\RecordTab\StaffViewAI',
                    'acquisitionpda' => 'finc\RecordTab\AcquisitionPDA',
                ],
            ],
        ],
        'recorddriver_tabs' => [
            'finc\RecordDriver\SolrDefault' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 'Description' => 'Description',
                    'TOC' => 'TOC', 'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 'Map' => 'Map',
                    'Similar' => 'SimilarItemsCarousel',
                    'Details' => 'StaffViewArray',
                    'Additional' => 'Additional',
                ],
                'defaultTab' => null,
            ],
            'finc\RecordDriver\SolrMarc' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 'Description' => 'Description',
                    'TOC' => 'TOC', 'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 'Map' => 'Map',
                    'Similar' => 'SimilarItemsCarousel',
                    'Details' => 'StaffViewMARC',
                    'Additional' => 'Additional',
                ],
                'defaultTab' => null,
            ],
            'finc\RecordDriver\SolrMarcFincPDA' => [
                'tabs' => [
                    /* 'Holdings' => 'HoldingsILS',*/
                    'AcquisitionPDA' => 'AcquisitionPDA',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 'Map' => 'Map',
                    'Similar' => 'SimilarItemsCarousel',
                    'Details' => 'StaffViewMARC',
                    'Additional' => 'Additional',
                ],
                'defaultTab' => null,
            ],
            'finc\RecordDriver\SolrAI' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 'Description' => 'Description',
                    'TOC' => 'TOC', 'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 'Map' => 'Map',
                    'Similar' => 'SimilarItemsCarousel',
                    'Details' => 'StaffViewAI',
                    'Additional' => 'Additional',
                ],
                'defaultTab' => null,
            ],
        ],
    ],
    // Authorization configuration:
    'zfc_rbac' => [
        'vufind_permission_provider_manager' => [
            'factories' => [
                'catUserType' => 'finc\Role\PermissionProvider\Factory::getCatUserType',
            ],
        ],
    ],
];

$nonTabRecordActions = [
    'PDA'
];


// Define record view routes -- route name => controller
// Define record view routes once again to add new nonTabRecordActions
$recordRoutes = [
    'record' => 'Record'
];


// Define static routes -- Controller/Action strings
$staticRoutes = [
    'MyResearch/Acquisition'
];

$routeGenerator = new \VuFind\Route\RouteGenerator($nonTabRecordActions);
$routeGenerator->addRecordRoutes($config, $recordRoutes);
//$routeGenerator->addDynamicRoutes($config, $dynamicRoutes);
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;
