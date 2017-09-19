<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'System>Install',
    'description' => 'The Install Tool mounted as the module Tools>Install in TYPO3.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '8.7.8',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.1.99',
            'typo3' => '8.7.8',
            'extbase' => '8.7.8',
            'fluid' => '8.7.8',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
