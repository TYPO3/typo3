<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'ADOdb',
    'description' => 'This extension just includes a current version of ADOdb, a database abstraction library for PHP, for further use in TYPO3',
    'category' => 'misc',
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
            'typo3' => '8.4.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
