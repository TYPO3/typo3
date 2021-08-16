<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Help>About',
    'description' => 'Shows info about TYPO3, installed extensions and a separate module for available modules.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '9.5.31',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.31',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
