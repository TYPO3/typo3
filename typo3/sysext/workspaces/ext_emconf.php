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
    'version' => '9.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.0.0-9.0.99',
            'version' => '9.0.0-9.0.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
