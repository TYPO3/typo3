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
    'version' => '12.4.37',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.37',
        ],
        'conflicts' => [],
        'suggests' => [
            'test_configoverride_first' => '12.4.37',
        ],
    ],
];
