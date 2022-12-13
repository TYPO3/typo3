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
    'version' => '12.1.2',
    'constraints' => [
        'depends' => [
            'typo3' => '12.1.2',
            'a' => '12.1.2',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
