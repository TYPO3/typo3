<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS SEO',
    'description' => 'SEO features including specific fields for SEO purposes, rendering of HTML meta tags and sitemaps.',
    'category' => 'fe',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '13.4.12',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.12',
        ],
        'conflicts' => [],
        'suggests' => [
            'dashboard' => '',
        ],
    ],
];
