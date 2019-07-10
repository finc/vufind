<?php
namespace BszTheme\Module\Configuration;

$config = [
    'service_manager' => [
        'factories' => [
             BszTheme\ThemeInfo::class => \BszTheme\ThemeInfoFactory::class,
            //'BszTheme\ThemeInfo' => 'BszTheme\Factory::getThemeInfo',
        ]
    ]
];
return $config;