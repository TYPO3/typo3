<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Help>About',
    'description' => 'Shows info about TYPO3, installed extensions and a separate module for available modules.',
    'category' => 'module',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '10.4.26',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.26',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
