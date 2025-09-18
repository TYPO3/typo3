<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'test extension',
    'description' => '',
    'category' => '',
    'version' => '14.0.0',
    'state' => 'beta',
    'author' => 'Helmut Hummel',
    'author_email' => 'helmut@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
