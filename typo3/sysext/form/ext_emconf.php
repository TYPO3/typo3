<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Form',
    'description' => 'Form Library, Plugin and Editor',
    'category' => 'misc',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '11.5.17',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.17',
        ],
        'conflicts' => [],
        'suggests' => [
            'filelist' => '11.5.17',
            'impexp' => '11.5.17',
        ],
    ],
];
