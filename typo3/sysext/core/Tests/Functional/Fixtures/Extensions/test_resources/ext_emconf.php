<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Test Resources',
    'description' => 'Test Resources',
    'category' => 'example',
    'version' => '11.5.0',
    'state' => 'beta',
    'clearCacheOnLoad' => 0,
    'author' => 'Oliver Hader',
    'author_email' => 'oliver@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
