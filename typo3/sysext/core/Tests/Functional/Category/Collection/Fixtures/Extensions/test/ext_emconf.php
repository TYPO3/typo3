<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'test extension',
    'description' => '',
    'category' => '',
    'version' => '11.5.1',
    'state' => 'beta',
    'clearCacheOnLoad' => 0,
    'author' => 'Christian Kuhn',
    'author_email' => 'lolli@schwarzbu.ch',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
