<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'User>Task Center',
    'description' => 'The Task Center is the framework for a host of other extensions, see below.',
    'category' => 'module',
    'state' => 'stable',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '10.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0',
        ],
        'conflicts' => [],
        'suggests' => [
            'sys_action' => '10.0.0',
        ],
    ],
];
