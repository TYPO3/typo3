<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Frontend Login for Website Users',
    'description' => 'A template-based plugin to log in Website Users in the Frontend',
    'category' => 'plugin',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '11.5.1',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
