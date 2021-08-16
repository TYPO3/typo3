<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Form',
    'description' => 'Form Library, Plugin and Editor',
    'category' => 'misc',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '9.5.31',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.31',
        ],
        'conflicts' => [],
        'suggests' => [
            'filelist' => '9.5.31',
            'impexp' => '9.5.31',
        ],
    ],
];
