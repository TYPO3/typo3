<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Scheduler',
    'description' => 'Schedule tasks to run once or periodically at a specific time.',
    'category' => 'misc',
    'version' => '12.0.0',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
