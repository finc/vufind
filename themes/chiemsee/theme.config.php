<?php
return array(
    'extends' => 'bodensee',
    
    'css' => array(
        'compiled.css',
    ),
    'js' => array(
//        'global.js', 
        'vendor/jquery.min.js', 
        'vendor/bootstrap.min.js', 
        'vendor/bootstrap-slider.js',
//        'vendor/bootstrap-accessibility.min.js',
        // 'vendor/bootlint.min.js',
//        'vendor/typeahead.js',
        'vendor/validator.min.js:async', 
        'vendor/rc4.js:async', 
        'lightbox.js:defer', 
        'common.min.js:defer',
    ),
    'less' => array(
        'active' => false,
        'compiled.less'
    ),
//    'favicon' => '/themes/bodensee/images/favicon/default.ico',
//    'helpers' => array(
//        'factories' => array(//            
//            
//        ),
//        'invokables' => array(

//        )
//    )
);
