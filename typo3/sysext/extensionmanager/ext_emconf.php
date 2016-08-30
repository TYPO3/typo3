<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Extension Manager',
    'description' => 'TYPO3 Extension Manager',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => '',
    'author_email' => '',
    'author_company' => '',
    'version' => '8.3.0',
    'constraints' => [
        'depends' => [
            'typo3' => '8.3.0-8.3.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
