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
    'version' => '7.6.32',
    'constraints' => [
        'depends' => [
            'adodb' => '7.6.32',
            'typo3' => '7.6.32',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
