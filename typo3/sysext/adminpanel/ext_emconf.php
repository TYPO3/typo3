<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Admin Panel',
    'description' => 'Panel with additional functionality in the frontend (Debugging, Caching, Preview...).',
    'category' => 'fe',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '12.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
