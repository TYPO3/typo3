<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'File>List',
    'description' => 'Listing of files in the directory',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '8.7.16',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.2.99',
            'typo3' => '8.7.16',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
