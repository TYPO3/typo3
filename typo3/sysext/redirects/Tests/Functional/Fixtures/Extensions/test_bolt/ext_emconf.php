<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'This extension simulates b13/bolt.',
    'description' => 'This extension simulates b13/bolt.',
    'category' => 'example',
    'version' => '12.4.35',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.35',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
