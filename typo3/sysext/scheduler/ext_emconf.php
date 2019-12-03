<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Scheduler',
    'description' => 'The TYPO3 Scheduler let\'s you register tasks to happen at a specific time',
    'category' => 'misc',
    'version' => '10.3.0',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '10.3.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
