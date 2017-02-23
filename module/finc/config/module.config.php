<?php
namespace finc\Module\Configuration;

$config = [
    'service_manager' => [
        'factories' => [
            'VuFind\Mailer' => 'finc\Mailer\Factory',
            'VuFind\CacheManager' => 'finc\Service\Factory::getCacheManager',
            'VuFind\BranchesReader' => 'finc\Service\Factory::getBranchesReader',
            'VuFind\ILSConnection' => 'finc\Service\Factory::getILSConnection',
            'VuFind\ILSHoldLogic' => 'finc\Service\Factory::getILSHoldLogic',
            'finc\Rewrite' => 'finc\Rewrite\Factory',
            'VuFind\SessionManager' => 'finc\Session\ManagerFactory',
            'VuFind\CookieManager' => 'finc\Service\Factory::getCookieManager'
        ]
    ],
    'controllers' => [
        'factories' => [
            'record' => 'finc\Controller\Factory::getRecordController',
            'dds' => 'finc\Controller\Factory::getDocumentDeliveryServiceController',
        ],
        'invokables' => [
            'ajax' => 'finc\Controller\AjaxController',
            'my-research' => 'finc\Controller\MyResearchController'
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'emailhold' => 'finc\Controller\Plugin\Factory::getEmailHold',
        ]
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
            'recommend' => [
                'factories' => [
                    'ebscoresults' => 'finc\Recommend\Factory::getEbscoResults'
                ]
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
                    'solris' => 'finc\RecordDriver\Factory::getSolrIS'
                ],
            ],
            'recordtab' => [
                'factories' => [
                    'hierarchytree' => 'finc\RecordTab\Factory::getHierarchyTree',
                ],
                'invokables' => [
                    'staffviewai' => 'finc\RecordTab\StaffViewAI',
                    'acquisitionpda' => 'finc\RecordTab\AcquisitionPDA',
                    'topics' => 'finc\RecordTab\Topics',
                ],
            ],
            'resolver_driver' => [
                'factories' => [
                    'ezb' => 'finc\Resolver\Driver\Factory::getEzb',
                    'redi' => 'finc\Resolver\Driver\Factory::getRedi'
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
                    'Similar' => null,
                    'Details' => 'StaffViewArray',
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
                    'Similar' => null,
                    'Details' => 'StaffViewMARC',
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
                    'Similar' => null,
                    'Details' => 'StaffViewMARC',
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
                    'Similar' => null,
                    'Details' => 'StaffViewAI',
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
                'ipRangeFoFor' => 'finc\Role\PermissionProvider\Factory::getIpRangeFoFor',
                'ipRegExFoFor' => 'finc\Role\PermissionProvider\Factory::getIpRegExFoFor',
            ],
        ],
    ],
];

$nonTabRecordActions = [
    'PDA', 'EmailHold'
];


// Define record view routes -- route name => controller
// Define record view routes once again to add new nonTabRecordActions
$recordRoutes = [
    'record' => 'Record'
];


// Define static routes -- Controller/Action strings
$staticRoutes = [
    'MyResearch/Acquisition', 'MyResearch/ResetPassword', 'dds/Home', 'dds/Email',
    'Record/EblLink'
];

$routeGenerator = new \VuFind\Route\RouteGenerator($nonTabRecordActions);
$routeGenerator->addRecordRoutes($config, $recordRoutes);
//$routeGenerator->addDynamicRoutes($config, $dynamicRoutes);
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;
