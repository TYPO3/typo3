<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Database Abstraction Layer',
    'description' => 'A database abstraction layer implementation for TYPO3 4.6 based on ADOdb and offering a lot of other features.',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Xavier Perseguers',
    'author_email' => 'xavier@typo3.org',
    'author_company' => '',
    'version' => '8.4.0',
    'constraints' => [
        'depends' => [
            'adodb' => '8.4.0-8.4.99',
            'typo3' => '8.4.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
