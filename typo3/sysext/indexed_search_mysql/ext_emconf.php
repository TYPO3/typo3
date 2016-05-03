<?php
$EM_CONF[$_EXTKEY] = array(
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
    'version' => '8.2.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.2.0-8.2.99',
            'indexed_search' => '8.2.0-8.2.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
