<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'System Reports',
    'description' => 'The reports module groups several system reports.',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '10.4.11',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.11',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => ''
        ],
    ],
];
