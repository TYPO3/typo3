<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Backend Styleguide and Testing use cases',
    'description' => 'TYPO3 extension to showcase TYPO3 Backend capabilities',
    'category' => 'plugin',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'state' => 'stable',
    'version' => '14.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.3',
            'felogin' => '14.0.3',
            'fluid_styled_content' => '14.0.3',
            'seo' => '14.0.3',
            'form' => '14.0.3',
            'indexed_search' => '14.0.3',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
