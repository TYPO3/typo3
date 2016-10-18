<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Frontend library',
    'description' => 'Classes for the frontend of TYPO3.',
    'category' => 'fe',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Kasper Skaarhoj',
    'author_email' => 'kasperYYYY@typo3.com',
    'author_company' => '',
    'version' => '8.5.0',
    'constraints' => [
        'depends' => [
            'typo3' => '8.5.0-8.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
