<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Import/Export',
    'description' => 'Import and Export of data and files of TYPO3 in XML or a custom serialized format T3D.',
    'category' => 'be',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '11.5.1',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
