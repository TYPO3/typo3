<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Extension Manager',
    'description' => 'Backend module (System > Extensions) for viewing and managing extensions.',
    'category' => 'module',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '14.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.2',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
