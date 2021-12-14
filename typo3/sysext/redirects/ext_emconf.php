<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Redirects',
    'description' => 'Manage redirects for your TYPO3-based website.',
    'category' => 'fe',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '11.5.5',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.5',
        ],
        'conflicts' => [],
        'suggests' => [
            'reports' => '',
            'scheduler' => '',
        ],
    ],
];
