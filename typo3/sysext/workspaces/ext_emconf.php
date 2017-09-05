<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Workspaces Management',
    'description' => 'Adds workspaces functionality with custom stages to TYPO3.',
    'category' => 'be',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'version' => '8.7.6',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.1.99',
            'typo3' => '8.7.6',
            'version' => '8.7.6',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
