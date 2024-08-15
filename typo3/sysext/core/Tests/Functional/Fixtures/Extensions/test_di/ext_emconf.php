<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'This extension contains dependency injection fixtures.',
    'description' => 'This extension contains dependency injection fixture.',
    'category' => 'example',
    'version' => '12.4.20',
    'state' => 'beta',
    'author' => 'Benjamin Franzke',
    'author_email' => 'ben@bnf.dev',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.20',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
