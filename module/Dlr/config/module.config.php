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
                    'SolrDlrMarc'                   =>  'Dlr\RecordDriver\SolrDlrMarc',                    
                    'SolrNtrsOai'                   =>  'Dlr\RecordDriver\SolrNtrsOai',                    
                ],
                'delegators' => [
                    'Bsz\RecordDriver\SolrDlrMarc'     => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriverSolrNtrsOai'      => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                ]
            ],
        ]
    ]
    
];
