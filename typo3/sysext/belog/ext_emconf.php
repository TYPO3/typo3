<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Tools>Log',
    'description' => 'Displays backend log, both per page and system wide. Available as the module Tools>Log (system wide overview) and Web>Info/Log (page relative overview).',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '9.5.31',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.31',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
