<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'test extension',
    'description' => '',
    'category' => '',
    'version' => '13.4.19',
    'state' => 'beta',
    'author' => 'Christian Kuhn',
    'author_email' => 'lolli@schwarzbu.ch',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.19',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
