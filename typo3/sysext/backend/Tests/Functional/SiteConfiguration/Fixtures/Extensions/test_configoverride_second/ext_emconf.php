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
    'version' => '13.4.8',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.8',
        ],
        'conflicts' => [],
        'suggests' => [
            'test_configoverride_first' => '13.4.8',
        ],
    ],
];
