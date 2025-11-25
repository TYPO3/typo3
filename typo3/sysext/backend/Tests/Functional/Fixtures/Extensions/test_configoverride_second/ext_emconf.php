<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => '',
    'description' => '',
    'category' => 'example',
    'author' => '',
    'author_company' => '',
    'author_email' => '',
    'state' => 'stable',
    'version' => '14.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.1.0',
        ],
        'conflicts' => [],
        'suggests' => [
            'test_configoverride_first' => '14.1.0',
        ],
    ],
];
