<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS LinkValidator',
    'description' => 'Checks for broken links and displays results in the (Info>LinkValidator) backend module.',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '10.4.35',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.35',
            'info' => '10.4.35',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '',
        ],
    ],
];
