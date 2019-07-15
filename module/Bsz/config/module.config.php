<?php
namespace Bsz\Module\Configuration;


$config = [
    'controllers' => [
        'invokables' => [
            'VuFind\Controller\CartController' => 'Bsz\Controller\CartController',
            'Bsz\Controller\HoldingController' => 'Bsz\Controller\HoldingController',
            'Bsz\Controller\ShibController' => 'Bsz\Controller\ShibController',
        ],
        'factories' => [
            'VuFind\Controller\AjaxController' => 'Bsz\Controller\Factory::getAjaxController',
            'VuFind\Controller\RecordController' => 'Bsz\Controller\Factory::getRecordController',
            'VuFind\Controller\SearchController' => 'Bsz\Controller\Factory::getSearchController',
            'VuFind\Controller\MyResearchController' => 'Bsz\Controller\Factory::getMyResearchController',
            'VuFind\Controller\EdsrecordController' => 'Bsz\Controller\Factory::getEdsrecordController',
            'Bsz\Controller\BszController' => 'Bsz\Controller\Factory::getBszController',
            'Bsz\Controller\TestController' => 'Bsz\Controller\Factory::getTestController',
        ],
        'aliases' => [
            'Bsz' => 'Bsz\Controller\BszController',
            'Test' => 'Bsz\Controller\TestController',
            'Shib' => 'Bsz\Controller\ShibController',
            'Holding' => 'Bsz\Controller\HoldingController',
        ]
    ],
    'router' => [
        'routes' => [
            'saveisil'=> [
                'type'    => 'Segment',
                'options' => [
                    'route'    => "/Bsz/saveIsil/:isil",
                    'constraints' => [
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'isil'       => 'DE-[a-zA-Z0-9\-\/,]+'
                    ],
                    'defaults' => [
                        'controller' => 'Bsz',
                        'action'     => 'saveIsil',
                    ]
                ]
                
            ]        
        ],
    ],    
    'service_manager' => [
        'factories' => [
            'Bsz\Config\Client'     => 'Bsz\Config\Factory::getClient', 
            'Bsz\Config\Libraries'  => 'Bsz\Config\Factory::getLibrariesTable',  
            'Bsz\Config\Dedup'  => 'Bsz\Config\Factory::getDedup',  
            'LibrariesTableGateway' => 'Bsz\Config\Factory::getLibrariesTableGateway',            
            'PlacesTableGateway' => 'Bsz\Config\Factory::getPlacesTableGateway',            
            'Bsz\Holding'    => 'Bsz\Factory::getHolding',
            'Bsz\Parser\OpenUrl' => 'Bsz\Parser\Factory::getOpenUrlParser',
            // override the factory, to make filters dynamic
            'VuFind\SearchTabsHelper' => 'Bsz\Service\Factory::getSearchTabsHelper',
            'VuFind\AuthManager' => 'Bsz\Auth\Factory::getManager',
            'VuFind\AuthManager' => 'Bsz\Auth\Factory::getManager',
        ],
        'invokables' => [
            'Bsz\Mapper'     => 'Bsz\FormatMapper',
            'Bsz\Config\Library'    => 'Bsz\Config\Library',
        ],
    ],
    'view_manager' => [
        'display_exceptions'       => APPLICATION_ENV == 'development' || APPILCATION_ENV=='production',
    ],
    
    'vufind' => [
        'plugin_managers' => [  
            'auth' => [
                'factories' => [
                   'shibboleth' => 'Bsz\Auth\Factory::getShibboleth'
                ]
            ],
            'recommend' => [
                'factories' => [
                    'sidefacets' => 'Bsz\Recommend\Factory::getSideFacets',
                    'searchbuttons' => 'Bsz\Recommend\Factory::getSearchButtons',
                    'rssfeedresults' => 'Bsz\Recommend\Factory::getRSSFeedResults',
                    'startpagenews' => 'Bsz\Recommend\Factory::getStartpageNews',
                ],
                'invokables' => [
                    'rssfeedresultsdeferred' => 'Bsz\Recommend\RSSFeedResultsDeferred',                                        
                ],
            ],            
            'recorddriver'  => [
                'factories' => [
                    'solrdefault' => 'Bsz\RecordDriver\Factory::getSolrDefault',
                    'solrgvimarc' => 'Bsz\RecordDriver\Factory::getSolrGviMarc'    ,                
                    'solrgvimarcde576' => 'Bsz\RecordDriver\Factory::getSolrGvimarcde576',
                    'solrgvimarcde600' => 'Bsz\RecordDriver\Factory::getSolrGvimarcde600',
                    'solrgvimarcde601' => 'Bsz\RecordDriver\Factory::getSolrGvimarcde601',
                    'solrgvimarcde602' => 'Bsz\RecordDriver\Factory::getSolrGvimarcde602',
                    'solrgvimarcde603' => 'Bsz\RecordDriver\Factory::getSolrGvimarcde603',
                    'solrgvimarcde604' => 'Bsz\RecordDriver\Factory::getSolrGvimarcde604',
                    'solrgvimarcde605' => 'Bsz\RecordDriver\Factory::getSolrGvimarcde605',
                    'solrgvimarcde101' => 'Bsz\RecordDriver\Factory::getSolrGvimarcde101',
                    'solrgvimarcde627' => 'Bsz\RecordDriver\Factory::getSolrGvimarcde627',
//                    'solrdlrmarc' => 'Bsz\RecordDriver\Factory::getSolrDlrMarc',
//                    'solrntrsoai' => 'Bsz\RecordDriver\Factory::getSolrNtrsoai',               
                     'eds' => 'Bsz\RecordDriver\Factory::getEDS',
                ],
            ],
            'recordtab' => [
                'factories' => [
                    'volumes' => 'Bsz\RecordTab\Factory::getVolumes',                    
                    'articles' => 'Bsz\RecordTab\Factory::getArticles',                    
                    'libraries' => 'Bsz\RecordTab\Factory::getLibraries',
                    'holdingsils' => 'Bsz\RecordTab\Factory::getHoldingsILS',
                
                ],
            ],
            'search_options' => [
                'abstract_factories' => ['Bsz\Search\Options\PluginFactory'],
                'factories' => [
                    'solr' => 'Bsz\Search\Options\Factory::getSolr'
                ],                
            ],
            'search_backend' => [
                'factories' => [
                    'Solr' => 'Bsz\Search\Factory\SolrDefaultBackendFactory',
                    'EDS' => 'Bsz\Search\Factory\EdsBackendFactory',
                ]
            ],
            'search_results' => [
                'abstract_factories' => ['Bsz\Search\Results\PluginFactory'],
                'factories' => [
                    'solr' => 'Bsz\Search\Results\Factory::getSolr',
                ],
            ],            
            'search_params'  => [
                'abstract_factories' => ['Bsz\Search\Params\PluginFactory'],
                'factories' => [
                    'solr' => 'Bsz\Search\Params\Factory::getSolr'
                ], 
            ],
            'ils_driver' => [
                'factories' => [
                    'daiabsz' => 'Bsz\ILS\Driver\Factory::getDAIAbsz',
                    'daia' => 'Bsz\ILS\Driver\Factory::getDAIA',
                    'noils' => 'Bsz\ILS\Driver\Factory::getNoILS',
                ]
            ],
            'resolver_driver' => [
                'abstract_factories' => ['VuFind\Resolver\Driver\PluginFactory'],
                'factories' => [
                    'ezb' => 'Bsz\Resolver\Driver\Factory::getEzb',
                    'redi' => 'Bsz\Resolver\Driver\Factory::getRedi',
                    'ill' => 'Bsz\Resolver\Driver\Factory::getIll',
                ],
            ]
        ],
        'recorddriver_tabs' => [
            'Bsz\RecordDriver\SolrGvimarc' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 
                    'Volumes' => 'Volumes',
                    'articles' => 'articles',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
                    'Libraries' => 'Libraries',                    
//                    'Similar' => 'SimilarItemsCarcousel',
                    'Details' => 'StaffViewMARC',
                    
                ],
                'defaultTab' => 'Holdings',
            ],
            'Bsz\RecordDriver\SolrGvimarcde576' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 
                    'Volumes' => 'Volumes',
                    'articles' => 'articles',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
                    'Libraries' => 'Libraries',                    
//                    'Similar' => 'SimilarItemsCarcousel',
                    'Details' => 'StaffViewMARC',
                    
                ],
                'defaultTab' => 'Holdings',
            ],
            'Bsz\RecordDriver\SolrGvimarcde600' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 
                    'Volumes' => 'Volumes',
                    'articles' => 'articles',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
                    'Libraries' => 'Libraries',                    
//                    'Similar' => 'SimilarItemsCarcousel',
                    'Details' => 'StaffViewMARC',
                    
                ],
                'defaultTab' => 'Holdings',
            ],
            'Bsz\RecordDriver\SolrGvimarcde601' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 
                    'Volumes' => 'Volumes',
                    'articles' => 'articles',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
                    'Libraries' => 'Libraries',                    
//                    'Similar' => 'SimilarItemsCarcousel',
                    'Details' => 'StaffViewMARC',
                    
                ],
                'defaultTab' => 'Holdings',
            ],
            'Bsz\RecordDriver\SolrGvimarcde602' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 
                    'Volumes' => 'Volumes',
                    'articles' => 'articles',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
                    'Libraries' => 'Libraries',                    
//                    'Similar' => 'SimilarItemsCarcousel',
                    'Details' => 'StaffViewMARC',
                    
                ],
                'defaultTab' => 'Holdings',
            ],
            'Bsz\RecordDriver\SolrGvimarcde603' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 
                    'Volumes' => 'Volumes',
                    'articles' => 'articles',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
                    'Libraries' => 'Libraries',                    
//                    'Similar' => 'SimilarItemsCarcousel',
                    'Details' => 'StaffViewMARC',
                    
                ],
                'defaultTab' => 'Holdings',
            ],
            'Bsz\RecordDriver\SolrGvimarcde604' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 
                    'Volumes' => 'Volumes',
                    'articles' => 'articles',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
                    'Libraries' => 'Libraries',                    
//                    'Similar' => 'SimilarItemsCarcousel',
                    'Details' => 'StaffViewMARC',
                    
                ],
                'defaultTab' => 'Holdings',
            ],
            'Bsz\RecordDriver\SolrGvimarcde605' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 
                    'Volumes' => 'Volumes',
                    'articles' => 'articles',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
                    'Libraries' => 'Libraries',                    
//                    'Similar' => 'SimilarItemsCarcousel',
                    'Details' => 'StaffViewMARC',
                    
                ],
                'defaultTab' => 'Holdings',
            ],
            'Bsz\RecordDriver\SolrGvimarcde101' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 
                    'Volumes' => 'Volumes',
                    'articles' => 'articles',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
                    'Libraries' => 'Libraries',                    
//                    'Similar' => 'SimilarItemsCarcousel',
                    'Details' => 'StaffViewMARC',
                    
                ],
                'defaultTab' => 'Holdings',
            ],
            'Bsz\RecordDriver\SolrNtrsoai' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 
//                    'Volumes' => 'Volumes',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
//                    'Similar' => 'SimilarItemsCarcousel',
                    'Details' => 'StaffViewArray',
                    
                ],
                'defaultTab' => 'Volumes',
            ],        
            'Bsz\RecordDriver\SolrDlrmarc' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 
                    'Volumes' => 'Volumes',
                    'Description' => 'Description',
                    'TOC' => 'TOC', 
                    'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 
                    'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 
                    'Map' => 'Map',
//                    'Similar' => 'SimilarItemsCarcousel',
                    'Details' => 'StaffViewMARC',
                    
                ],
                'defaultTab' => 'Volumes',
            ],
            
        ],
    ]
  
];
$staticRoutes = [
    'Test/Record', 'Test/phpinfo', 'Test/zfl',
    'Bsz/index', 'Bsz/curl',
    'Record/Freeform',
    'Holding/Query',
    'Bsz/Privacy',     
    'Bsz/Dedup',
    'Shib/Wayf', 'Shib/Redirect',
];
$recordRoutes = [
    'record' => 'Record',
];

$routeGenerator = new \Bsz\Route\RouteGenerator();
$routeGenerator->addRecordRoutes($config, $recordRoutes);
//$routeGenerator->addDynamicRoutes($config, $dynamicRoutes); 
$routeGenerator->addStaticRoutes($config, $staticRoutes);
return $config;


