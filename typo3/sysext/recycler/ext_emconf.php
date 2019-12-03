<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Recycler',
    'description' => 'The recycler offers the possibility to restore deleted records or remove them from the database permanently. These actions can be applied to a single record, multiple records, and recursively to child records (ex. restoring a page can restore all content elements on that page). Filtering by page and by table provides a quick overview of deleted records before taking action on them.',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author_company' => '',
    'version' => '10.3.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.3.0',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => ''
        ],
    ],
];
