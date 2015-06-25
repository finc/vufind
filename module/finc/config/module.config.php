<?php
namespace finc\Module\Configuration;

$config = [
    'controllers' => [
        'invokables' => [
            'my-research' => 'finc\Controller\MyResearchController',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'ils_driver' => [
                'factories' => [
                    'fincils' => 'finc\ILS\Driver\Factory::getFincILS',
                ],
                'invokables' => [
                    'daia' => 'finc\ILS\Driver\DAIA',
                    'paia' => 'finc\ILS\Driver\PAIA',
                ],
            ],
            'recorddriver' => [
                'factories' => [
                    'solrdefault' => 'finc\RecordDriver\Factory::getSolrDefault',
                    'solrmarc' => 'finc\RecordDriver\Factory::getSolrMarc',
                    'solrmarcfinc' => 'finc\RecordDriver\Factory::getSolrMarcFinc',
                    'solrmarcremote' => 'finc\RecordDriver\Factory::getSolrMarcRemote',
                    'solrmarcremotefinc' => 'finc\RecordDriver\Factory::getSolrMarcRemoteFinc',
                    'solrai' => 'finc\RecordDriver\Factory::getSolrAI',
                ],
            ],
            'resolver_driver' => [
                'factories' => [
                    'redi' => 'finc\Resolver\Driver\Factory::getRedi',
                ],
            ],
            'recordtab' => [
                'invokables' => [
                    'additional' => 'finc\RecordTab\Additional',
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
        ],
    ],
];

// Define static routes -- Controller/Action strings
$staticRoutes = [
    'MyResearch/Acquisition'
];

// Build static routes
foreach ($staticRoutes as $route) {
    list($controller, $action) = explode('/', $route);
    $routeName = str_replace('/', '-', strtolower($route));
    $config['router']['routes'][$routeName] = [
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => [
            'route'    => '/' . $route,
            'defaults' => [
                'controller' => $controller,
                'action'     => $action,
            ]
        ]
    ];
}

return $config;
