<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Dashboard',
    'description' => 'TYPO3 backend module for configurable dashboards with widgets.',
    'category' => 'be',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '12.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
