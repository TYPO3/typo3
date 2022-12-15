<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Test Resources',
    'description' => 'Test Resources',
    'category' => 'example',
    'version' => '12.1.4',
    'state' => 'beta',
    'author' => 'Oliver Hader',
    'author_email' => 'oliver@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '12.1.4',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
