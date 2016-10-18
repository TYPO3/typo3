<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'User>Task Center',
    'description' => 'The Task Center is the framework for a host of other extensions, see below.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Kasper Skaarhoj',
    'author_email' => 'kasperYYYY@typo3.com',
    'author_company' => 'Curby Soft Multimedia',
    'version' => '8.5.0',
    'constraints' => [
        'depends' => [
            'typo3' => '8.5.0-8.5.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'sys_action' => '8.5.0-8.5.99',
        ],
    ],
];
