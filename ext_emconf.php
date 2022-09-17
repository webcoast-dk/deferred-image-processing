<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Deferred image processing',
    'description' => 'Handles image processing on request instead of during page generation',
    'category' => 'plugin',
    'author' => 'Thorben Nissen',
    'author_email' => 'thorben@webcoast.dk',
    'author_company' => 'WEBcoast',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.5',
    'constraints' => array(
        'depends' => array(
            'typo3' => '10.4.0-11.5.99'
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
