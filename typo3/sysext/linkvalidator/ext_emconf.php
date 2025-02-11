<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS LinkValidator',
    'description' => 'Checks for broken links and displays results in the (Info>LinkValidator) backend module.',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '13.4.6',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.6',
            'info' => '13.4.6',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '',
        ],
    ],
];
