<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'User>User Settings',
    'description' => 'Allows users to edit a limited set of options for their user profile, eg. preferred language and their name and email address.',
    'category' => 'module',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '11.5.1',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
