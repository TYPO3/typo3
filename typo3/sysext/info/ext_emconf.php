<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Info',
    'description' => 'TYPO3 backend module for displaying information, such as a pagetree overview and localization information.',
    'category' => 'module',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '12.4.10',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.10',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
