<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Dashboard',
    'description' => 'Add dashboard to TYPO3',
    'category' => 'be',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '11.5.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
