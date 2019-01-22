<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Context Sensitive Help',
    'description' => 'Provides context sensitive help to tables, fields and modules in the system languages.',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '8.7.24',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.2.99',
            'typo3' => '8.7.24',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
