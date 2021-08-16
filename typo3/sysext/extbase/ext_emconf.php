<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Extbase Framework for Extensions',
    'description' => 'A framework to build extensions for TYPO3 CMS.',
    'category' => 'misc',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'version' => '9.5.31',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.31',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => ''
        ],
    ],
];
