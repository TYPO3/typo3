<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Webhooks',
    'description' => 'Handle outgoing Webhooks for TYPO3',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '13.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
