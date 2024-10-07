<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Reports',
    'description' => 'Show status reports and installed services in the (System>Reports) backend module.',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '13.3.1',
    'constraints' => [
        'depends' => [
            'typo3' => '13.3.1',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '',
        ],
    ],
];
