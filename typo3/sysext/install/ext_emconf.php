<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Install Tool',
    'description' => 'Install Tool (Admin Tools) for installation, upgrade, system administration and setup.',
    'category' => 'module',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '12.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0',
            'extbase' => '12.0.0',
            'fluid' => '12.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
