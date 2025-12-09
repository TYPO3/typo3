<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Redirects',
    'description' => 'Create manual redirects, list existing redirects and automatically create redirects on slug changes.',
    'category' => 'fe',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '13.4.23',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.23',
        ],
        'conflicts' => [],
        'suggests' => [
            'reports' => '',
            'scheduler' => '',
        ],
    ],
];
