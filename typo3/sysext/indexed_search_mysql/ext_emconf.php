<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'MySQL driver for Indexed Search Engine',
    'description' => 'MySQL specific driver for Indexed Search Engine. Allows usage of MySQL-only features like FULLTEXT indexes.',
    'category' => 'misc',
    'state' => 'beta',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Michael Stucki',
    'author_email' => 'michael@typo3.org',
    'author_company' => '',
    'version' => '7.6.24',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.24',
            'indexed_search' => '7.6.24',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
