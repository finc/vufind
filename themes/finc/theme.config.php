<?php
return array(
    'extends' => 'foundation5',
    'css' => array(
        'finc.css',
    ),
    'helpers' => array(
        'factories' => array(
            'record' => 'finc\View\Helper\Root\Factory::getRecord',
            'citation' => 'finc\View\Helper\Root\Factory::getCitation',
        ),
        'invokables' => array(
            'resultfeed' => 'finc\View\Helper\Root\ResultFeed'
        )
    ),
);
