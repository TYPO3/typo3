<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Log',
    'description' => 'View logs from the sys_log table in the TYPO3 backend modules System>Log',
    'category' => 'module',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '13.4.3',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.3',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
