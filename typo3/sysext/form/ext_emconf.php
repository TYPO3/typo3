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
    'version' => '10.4.16',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.16',
        ],
        'conflicts' => [],
        'suggests' => [
            'filelist' => '10.4.16',
            'impexp' => '10.4.16',
        ],
    ],
];
