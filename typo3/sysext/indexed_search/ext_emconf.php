<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Indexed Search',
    'description' => 'Provides indexing functionality for TYPO3 pages and records as well as files including PDF, Word, HTML and plain text.',
    'category' => 'plugin',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '13.4.12',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.12',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '',
        ],
    ],
];
