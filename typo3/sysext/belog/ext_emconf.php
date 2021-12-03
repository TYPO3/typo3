<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Log',
    'description' => 'View logs from the sys_log table in the TYPO3 backend modules System>Log and Web>Info>Log.',
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
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
