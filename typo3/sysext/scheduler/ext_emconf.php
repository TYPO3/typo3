<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Scheduler',
    'description' => 'The TYPO3 Scheduler let\'s you register tasks to happen at a specific time',
    'category' => 'misc',
    'version' => '8.7.13',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.2.99',
            'typo3' => '8.7.13',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
