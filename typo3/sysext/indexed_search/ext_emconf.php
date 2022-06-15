<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Indexed Search Engine',
    'description' => 'Indexed Search Engine for TYPO3 pages, PDF-files, Word-files, HTML and text files. Provides a backend module for statistics of the indexer and a frontend plugin.',
    'category' => 'plugin',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '11.5.13',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.13',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '',
        ],
    ],
];
