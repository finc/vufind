<?php
namespace Bsz\Module\Configuration;


$config = [
    'controllers' => [
        'invokables' => [
            'ajax' => 'Bsz\Controller\AjaxController',
            'bsz' => 'Bsz\Controller\BszController',
//            'fis' => 'Bsz\Controller\FisController',
            'holding' => 'Bsz\Controller\HoldingController',
            'interlending' => 'Bsz\Controller\InterlendingController',
            'test' => 'Bsz\Controller\TestController',
            'cart' => 'Bsz\Controller\CartController',
            'privacy' => 'Bsz\Controller\BszController',
//            'shib' => 'Bsz\Controller\ShibController'
        ],
        'factories' => [
            'interlendingrecord' => 'Bsz\Controller\Factory::getInterlendingrecordController',
//            'fisrecord' => 'Bsz\Controller\Factory::getFisrecordController',
            'record' => 'Bsz\Controller\Factory::getRecordController'
        ],
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
            'bsz\config\client'     => 'Bsz\Config\Factory::getClient', 
            'bsz\libraries'  => 'Bsz\Config\Factory::getLibrariesTable',  
            'LibrariesTableGateway' => 'Bsz\Config\Factory::getLibrariesTableGateway',            
            'PlacesTableGateway' => 'Bsz\Config\Factory::getPlacesTableGateway',            
            'bsz\holding'    => 'Bsz\Factory::getHolding',
            'bsz\parser\openurl' => 'Bsz\Parser\Factory::getOpenUrlParser',
        ],
        'invokables' => [
            'bsz\mapper'     => 'Bsz\FormatMapper',
            'bsz\library'    => 'Bsz\Config\Library',
        ],
        'aliases' => [
            'bsz\client'    => 'Bsz\config\Client',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [            
            'recorddriver'  => [
                'factories' => [
                    'solrdefault' => 'Bsz\RecordDriver\Factory::getSolrDefault',
                    'solrgvimarc' => 'Bsz\RecordDriver\Factory::getSolrGviMarc'                    
//                    'solrdlrmarc' => 'Bsz\RecordDriver\Factory::getSolrDlrMarc',
//                    'solrntrsoai' => 'Bsz\RecordDriver\Factory::getSolrNtrsoai',                 
//                    'solrfismarc' => 'Bsz\RecordDriver\Factory::getSolrFisMarc',                    
                ],
            ],
            'recordtab' => [
                'factories' => [
                    'volumes' => 'Bsz\RecordTab\Factory::getVolumes',                    
                    'articles' => 'Bsz\RecordTab\Factory::getArticles',                    
                    'libraries' => 'Bsz\RecordTab\Factory::getLibraries',                    
                
                ],
            ],
            'search_options' => [
                'factories' => [
                    'interlending' => 'Bsz\Search\Options\Factory::getInterlending',
//                    'fis' => 'Bsz\Search\Options\Factory::getFis',
                    'solr' => 'Bsz\Search\Options\Factory::getSolr'
                ],                
            ],
            'search_params'  => [
                'factories' => [
                    'interlending' => 'Bsz\Search\Params\Factory::getInterlending',
//                    'fis' => 'Bsz\Search\Params\Factory::getFis',
                    'solr' => 'Bsz\Search\Params\Factory::getSolr'
                ], 
            ],
            'search_results'  => [
                'factories' => [
                    'interlending' => 'Bsz\Search\Results\Factory::getInterlending',
//                    'fis' => 'Bsz\Search\Results\Factory::getFis'
                ], 
            ],
            'ils_driver' => [
                'factories' => [
    //                'daia' => 'Bsz\ILS\Driver\Factory::getDAIA',
                    'daiaadis' => 'Bsz\ILS\Driver\Factory::getDAIAadis',
                ]
            ],
            
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
                    'Details' => 'StaffViewMARC',
                    
                ],
                'defaultTab' => 'Volumes',
            ],
            
        ],
    ]
  
];
$staticRoutes = [
    'Test/Record', 'Test/phpinfo',
    'Bsz/index', 'Bsz/curl',
    'Interlending/Home', 'Interlending/Results', 'Interlending/Advanced',
    'InterlendingRecord/Freeform',
//    'Fis/Home', 'Fis/Results', 'Fis/Advanced',    
    'Holding/Query',
    'Bsz/Privacy',     
//    'Shib/Wayf', 'Shib/Redirect',
];
$recordRoutes = [
    'interlendingrecord' => 'InterlendingRecord',
    'fisrecord' => 'FisRecord',
];

$routeGenerator = new \Bsz\Route\RouteGenerator();
$routeGenerator->addRecordRoutes($config, $recordRoutes);
//$routeGenerator->addDynamicRoutes($config, $dynamicRoutes); 
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;


