<?php
return array(
    'extends' => 'foundation5',
    'helpers' => array(
        'factories' => array(
            'record' => 'finc\View\Helper\Root\Factory::getRecord',
            'interlibraryloan' =>
                'finc\View\Helper\Root\Factory::getInterlibraryLoanLink',
            'citation' => 'finc\View\Helper\Root\Factory::getCitation',
        ),
        'invokables' => array(
            'resultfeed' => 'finc\View\Helper\Root\ResultFeed'
        )
    ),
);
