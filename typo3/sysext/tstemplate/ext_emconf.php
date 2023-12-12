<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS TypoScript',
    'description' => 'TYPO3 backend module for the management of TypoScript records for the CMS frontend.',
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
