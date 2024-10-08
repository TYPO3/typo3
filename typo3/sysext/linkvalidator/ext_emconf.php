<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS LinkValidator',
    'description' => 'Checks for broken links and displays results in the (Info>LinkValidator) backend module.',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '13.3.2',
    'constraints' => [
        'depends' => [
            'typo3' => '13.3.2',
            'info' => '13.3.2',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '',
        ],
    ],
];
