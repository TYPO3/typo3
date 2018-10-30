<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'CKEditor Rich Text Editor',
    'description' => 'Integration of CKEditor as Rich Text Editor.',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '9.5.2',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.2',
        ],
        'conflicts' => [],
        'suggests' => [
            'setup' => '9.5.2',
        ],
    ],
];
