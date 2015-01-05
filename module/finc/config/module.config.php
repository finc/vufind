<?php
namespace finc\Module\Configuration;

$config = array(
    'vufind' => array(
        'plugin_managers' => array(
            'ils_driver' => array(
                'factories' => array(
                    'fincdaia' => 'finc\ILS\Driver\Factory::getDAIA',
                ),
                'invokables' => array(
                    'daia' => 'finc\ILS\Driver\DAIA',
                    'paia' => 'finc\ILS\Driver\PAIA',
                ),
            ),
            'recorddriver' => array(
                'factories' => array(
                    'solrmarcremote' => 'finc\RecordDriver\Factory::getSolrMarcRemote'
                ),
            ),
        ),
        'recorddriver_tabs' => array(
            'finc\RecordDriver\SolrMarcRemote' => array(
                'tabs' => array(
                    'Holdings' => 'HoldingsILS', 'Description' => 'Description',
                    'TOC' => 'TOC', 'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 'Map' => 'Map',
                    'Details' => 'StaffViewMARC',
                ),
                'defaultTab' => null,
            ),
        ),
    ),
);

return $config;
