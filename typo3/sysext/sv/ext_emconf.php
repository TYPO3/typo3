<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 System Services',
    'description' => 'The core/default services. This includes the default authentication services for now.',
    'category' => 'services',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '8.7.8',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.1.99',
            'typo3' => '8.7.8',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
