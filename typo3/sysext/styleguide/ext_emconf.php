<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Backend Styleguide and Testing use cases',
    'description' => 'TYPO3 extension to showcase TYPO3 Backend capabilities',
    'category' => 'plugin',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'state' => 'stable',
    'version' => '13.4.15',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.15',
            'felogin' => '13.4.15',
            'fluid_styled_content' => '13.4.15',
            'seo' => '13.4.15',
            'form' => '13.4.15',
            'indexed_search' => '13.4.15',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
