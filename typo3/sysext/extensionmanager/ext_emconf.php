<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Extension Manager',
    'description' => 'TYPO3 Extension Manager',
    'category' => 'module',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '11.3.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.3.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
