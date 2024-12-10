<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'test extension',
    'description' => '',
    'category' => '',
    'version' => '12.4.25',
    'state' => 'beta',
    'author' => 'Christian Kuhn',
    'author_email' => 'lolli@schwarzbu.ch',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.25',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
