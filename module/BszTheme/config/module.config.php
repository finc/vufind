<?php
namespace BszTheme\Module\Configuration;

$config = [
    'service_manager' => [
        'factories' => [
            'BszTheme\ThemeInfo' => 'BszTheme\Factory::getThemeInfo',
        ]
    ]
];
return $config;