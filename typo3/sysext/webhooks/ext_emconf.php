<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Webhooks',
    'description' => 'Handle outgoing Webhooks for TYPO3',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '13.4.25',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.25',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
