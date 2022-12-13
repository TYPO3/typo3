<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'MetaData Test',
    'description' => 'MetaData Test',
    'category' => 'example',
    'version' => '11.5.21',
    'state' => 'beta',
    'author' => 'Frank Nägler',
    'author_email' => 'frank.naegler@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.21',
            'seo' => '11.5.21',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
