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
    'version' => '9.3.3',
    'constraints' => [
        'depends' => [
            'typo3' => '9.3.3',
            'extbase' => '9.3.3',
            'fluid' => '9.3.3',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
