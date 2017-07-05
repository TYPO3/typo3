<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Help>TYPO3 Manual',
    'description' => 'Shows TYPO3 inline user manual.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '9.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.0.0-9.0.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
