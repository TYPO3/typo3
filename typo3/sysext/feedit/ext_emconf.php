<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Frontend Editing',
    'description' => 'Adds basic frontend editing capabilities to TYPO3.',
    'category' => 'fe',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '8.7.25',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.2.99',
            'typo3' => '8.7.25',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
