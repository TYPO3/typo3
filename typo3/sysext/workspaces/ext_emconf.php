<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Workspaces Management',
    'description' => 'Adds versioning of records and workspaces functionality with custom stages to TYPO3.',
    'category' => 'be',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '11.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
