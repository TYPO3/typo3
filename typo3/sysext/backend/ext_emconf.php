<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Backend',
    'description' => 'Classes for the TYPO3 backend.',
    'category' => 'be',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '10.4.14',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.14',
            'recordlist' => '10.4.14',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
