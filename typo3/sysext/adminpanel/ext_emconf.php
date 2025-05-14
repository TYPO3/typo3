<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Admin Panel',
    'description' => 'The Admin Panel displays information about your site in the frontend and contains a range of metrics including debug and caching information.',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '13.4.12',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.12',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
