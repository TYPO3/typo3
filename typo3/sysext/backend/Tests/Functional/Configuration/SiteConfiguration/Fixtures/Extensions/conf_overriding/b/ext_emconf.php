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
    'uploadfolder' => 0,
    'clearCacheOnLoad' => 1,
    'version' => '11.5.1',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.1',
            'a' => '11.5.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
