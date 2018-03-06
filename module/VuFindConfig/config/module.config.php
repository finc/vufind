<?php

return [
    'service_manager' => [
        'factories' => [
            'VuFind\Config\Manager' => 'Zend\ServiceManager\Factory\InvokableFactory'
        ],
        'aliases' => [
            'VuFind\Config' => 'VuFind\Config\Manager'
        ]
    ]
];