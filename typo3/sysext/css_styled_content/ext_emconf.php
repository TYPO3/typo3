<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'CSS styled content',
    'description' => 'Contains configuration for CSS content-rendering of the table "tt_content". This is meant as a modern substitute for the classic "content (default)" template which was based more on <font>-tags, while this is pure CSS.',
    'category' => 'fe',
    'state' => 'deprecated',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '8.7.10',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.2.99',
            'typo3' => '8.7.10',
            'frontend' => '8.7.10',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
