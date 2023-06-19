<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Deferred image processing',
    'description' => 'Handles image processing on request instead of during page generation',
    'category' => 'plugin',
    'author' => 'Thorben Nissen',
    'author_email' => 'thorben@webcoast.dk',
    'author_company' => 'WEBcoast',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
