<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Admin Panel',
    'description' => 'The TYPO3 admin panel provides a panel with additional functionality in the frontend (Debugging, Caching, Preview...)',
    'category' => 'fe',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '9.5.3',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.3',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
