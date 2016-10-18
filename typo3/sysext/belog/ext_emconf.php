<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Tools>Log',
    'description' => 'Displays backend log, both per page and system wide. Available as the module Tools>Log (system wide overview) and Web>Info/Log (page relative overview).',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Christian Kuhn',
    'author_email' => '',
    'author_company' => '',
    'version' => '8.5.0',
    'constraints' => [
        'depends' => [
            'typo3' => '8.5.0-8.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
