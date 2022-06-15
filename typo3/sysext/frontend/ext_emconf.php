<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Frontend library',
    'description' => 'Classes for the frontend of TYPO3.',
    'category' => 'fe',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '11.5.13',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.13',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
