<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Backend Styleguide and Testing use cases',
    'description' => 'TYPO3 extension to showcase TYPO3 Backend capabilities',
    'category' => 'plugin',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'state' => 'stable',
    'version' => '14.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.2',
            'felogin' => '14.0.2',
            'fluid_styled_content' => '14.0.2',
            'seo' => '14.0.2',
            'form' => '14.0.2',
            'indexed_search' => '14.0.2',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
