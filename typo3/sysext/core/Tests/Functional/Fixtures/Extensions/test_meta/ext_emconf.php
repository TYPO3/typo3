<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'MetaData Test',
    'description' => 'MetaData Test',
    'category' => 'example',
    'version' => '10.4.14',
    'state' => 'beta',
    'clearCacheOnLoad' => 0,
    'author' => 'Frank Nägler',
    'author_email' => 'frank.naegler@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.14',
            'seo' => '10.4.14',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
