<?php
return array(
    'extends' => 'bootstrap3',
    'helpers' => array(
        'factories' => array(
            'record' => 'finc\View\Helper\Root\Factory::getRecord'
        ),
    ),
);
