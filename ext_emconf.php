<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Deferred image processing',
    'description' => 'Handles image processing on request instead of during page generation',
    'category' => 'plugin',
    'state' => 'stable',
    'version' => '2.0.0',
    'author' => 'Thorben Nissen',
    'author_email' => 'thorben@webcoast.dk',
    'author_company' => 'WEBcoast',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.99.99',
            'typo3' => '11.5.0-12.4.99'
        ]
    ]
];
