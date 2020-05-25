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
    'version' => '11.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => ''
        ],
    ],
];
