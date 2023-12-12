<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Lowlevel',
    'description' => 'Technical analysis of the system. This includes raw database search, checking relations, counting pages and records etc.',
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
