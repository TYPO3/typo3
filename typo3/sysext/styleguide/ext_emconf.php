<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Backend Styleguide and Testing use cases',
    'description' => 'TYPO3 extension to showcase TYPO3 Backend capabilities',
    'category' => 'plugin',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'state' => 'stable',
    'version' => '13.4.33',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.33',
            'felogin' => '13.4.33',
            'fluid_styled_content' => '13.4.33',
            'seo' => '13.4.33',
            'form' => '13.4.33',
            'indexed_search' => '13.4.33',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
