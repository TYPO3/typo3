<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Functional test related extension derived from blog_example',
    'description' => 'A test related extension used to verify various extbase features',
    'category' => 'example',
    'author' => 'TYPO3 core team',
    'author_company' => '',
    'author_email' => '',
    'state' => 'stable',
    'version' => '13.4.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
