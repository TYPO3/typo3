<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'CKEditor Rich Text Editor',
    'description' => 'Integration of CKEditor as Rich Text Editor.',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => 1,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'info@typo3.org',
    'version' => '8.7.3',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-8.7.3',
        ],
        'conflicts' => [],
        'suggests' => [
            'setup' => '',
        ],
    ],
];
