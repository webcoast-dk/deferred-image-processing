<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Deferred image processing',
    'description' => 'Handles image processing on request instead of during page generation',
    'category' => 'plugin',
    'state' => 'stable',
    'version' => '3.0.3',
    'author' => 'Thorben Nissen',
    'author_email' => 'thorben@webcoast.dk',
    'author_company' => 'WEBcoast',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.99.99',
            'typo3' => '12.4.0-13.4.99'
        ]
    ]
];
