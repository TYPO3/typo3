<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS LinkValidator',
    'description' => 'Checks for broken links and displays results in the (Status > Check Links) backend module.',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '14.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.2',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '',
        ],
    ],
];
