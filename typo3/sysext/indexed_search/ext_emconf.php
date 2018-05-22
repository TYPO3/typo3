<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Indexed Search Engine',
    'description' => 'Indexed Search Engine for TYPO3 pages, PDF-files, Word-files, HTML and text files. Provides a backend module for statistics of the indexer and a frontend plugin. Documentation can be found in the extension "doc_indexed_search".',
    'category' => 'plugin',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '8.7.15',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.2.99',
            'typo3' => '8.7.15',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '',
        ],
    ],
];
