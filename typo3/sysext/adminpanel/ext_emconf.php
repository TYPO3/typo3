<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Admin Panel',
    'description' => 'The TYPO3 admin panel provides a panel with additional functionality in the frontend (Debugging, Caching, Preview...)',
    'category' => 'fe',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '11.5.12',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.12',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
