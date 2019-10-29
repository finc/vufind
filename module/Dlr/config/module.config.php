<?php

namespace Dlr\Module\Config;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'recorddriver'  => [
                'factories' => [
                    'Dlr\RecordDriver\SolrDlrMarc'         => 'Dlr\RecordDriver\Factory',
                    'Dlr\RecordDriver\SolrNtrsOai'         => 'Dlr\RecordDriver\Factory',
                ],
                'aliases' => [                    
                    'solrdlrmarc'                   =>  'Dlr\RecordDriver\SolrDlrMarc',                    
                    'solrntrsoai'                   =>  'Dlr\RecordDriver\SolrNtrsOai',                    
                ],
                'delegators' => [
                    'Bsz\RecordDriver\SolrDlrMarc'     => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriverSolrNtrsOai'      => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                ]
            ],
        ]
    ],    
    'view_manager' => [
        'display_exceptions'       => APPLICATION_ENV == 'development',
    ],
];
return $config;

