<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'CKEditor Rich Text Editor',
    'description' => 'Integration of CKEditor as Rich Text Editor.',
    'category' => 'be',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '11.5.7',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.7',
        ],
        'conflicts' => [],
        'suggests' => [
            'setup' => '11.5.7',
        ],
    ],
];
