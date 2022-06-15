<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Extbase Framework for Extensions',
    'description' => 'A framework to build extensions for TYPO3 CMS.',
    'category' => 'misc',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '11.5.13',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.13',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '',
        ],
    ],
];
