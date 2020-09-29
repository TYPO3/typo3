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
    'version' => '10.4.10',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.10',
        ],
        'conflicts' => [],
        'suggests' => [
            'filelist' => '10.4.10',
            'impexp' => '10.4.10',
        ],
    ],
];
