<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Tools>Log',
    'description' => 'Displays backend log, both per page and system wide. Available as the module Tools>Log (system wide overview) and Web>Info/Log (page relative overview).',
    'category' => 'module',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '11.5.13',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.13',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
