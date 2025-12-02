<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'ResourceRendering',
    'description' => 'ResourceRendering',
    'category' => 'example',
    'version' => '14.0.1',
    'state' => 'beta',
    'author' => 'Helmut Hummel',
    'author_email' => 'helmut@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.1',
            'frontend' => '14.0.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
