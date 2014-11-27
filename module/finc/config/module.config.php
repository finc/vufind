<?php
namespace finc\Module\Configuration;

$config = array(
    'vufind' => array(
        'plugin_managers' => array(
            'recorddriver' => array(
                'factories' => array(
                    'solrmarclite' => 'finc\RecordDriver\Factory::getSolrMarcLite'
                ),
            ),
        ),
        'recorddriver_tabs' => array(
            'finc\RecordDriver\SolrMarcLite' => array(
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
