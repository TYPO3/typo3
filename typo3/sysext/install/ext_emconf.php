<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'System>Install',
    'description' => 'The Install Tool mounted as the module Tools>Install in TYPO3.',
    'category' => 'module',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '10.4.26',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.26',
            'extbase' => '10.4.26',
            'fluid' => '10.4.26',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
