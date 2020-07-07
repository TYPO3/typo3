<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'CKEditor Rich Text Editor',
    'description' => 'Integration of CKEditor as Rich Text Editor.',
    'category' => 'be',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '10.4.6',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.6',
        ],
        'conflicts' => [],
        'suggests' => [
            'setup' => '10.4.6',
        ],
    ],
];
