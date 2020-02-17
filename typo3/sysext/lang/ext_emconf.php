<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'System language labels',
    'description' => 'Contains all the core language labels in a set of files mostly of the "locallang" format. This extension is always required in a TYPO3 install.',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '8.7.32',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.32',
            'extensionmanager' => '8.7.32',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
