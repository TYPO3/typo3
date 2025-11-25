<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Backend Styleguide and Testing use cases',
    'description' => 'TYPO3 extension to showcase TYPO3 Backend capabilities',
    'category' => 'plugin',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'state' => 'stable',
    'version' => '14.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.1.0',
            'felogin' => '14.1.0',
            'fluid_styled_content' => '14.1.0',
            'seo' => '14.1.0',
            'form' => '14.1.0',
            'indexed_search' => '14.1.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
