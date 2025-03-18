<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Reactions',
    'description' => 'Handle incoming Webhooks for TYPO3',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'state' => 'stable',
    'author_company' => '',
    'version' => '13.4.9',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.9',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
