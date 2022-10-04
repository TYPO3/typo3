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
    'clearCacheOnLoad' => 1,
    'version' => '12.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.1.0',
            'a' => '12.1.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
