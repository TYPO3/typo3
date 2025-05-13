<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Scheduler',
    'description' => 'Schedule tasks to run once or periodically at a specific time.',
    'category' => 'misc',
    'version' => '13.4.11',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.11',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
