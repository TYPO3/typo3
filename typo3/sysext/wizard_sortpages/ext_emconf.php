<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Web>Functions: Sort pages',
    'description' => 'A little utility to rearrange the sorting order of pages in the backend.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '8.7.8',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.1.99',
            'typo3' => '8.7.8',
            'func' => '8.7.8',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
