<?php

$config = [
    'service_manager' => [
        'factories' => [
            'BszTheme\ThemeInfo' => 'BszTheme\Factory::getThemeInfo',
        ]
    ]
];
return $config;