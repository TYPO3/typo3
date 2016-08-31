<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'User>Open Documents',
    'description' => 'Shows opened documents by the user.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Benjamin Mack',
    'author_email' => 'mack@xnos.org',
    'author_company' => '',
    'version' => '8.4.0',
    'constraints' => [
        'depends' => [
            'typo3' => '8.4.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
