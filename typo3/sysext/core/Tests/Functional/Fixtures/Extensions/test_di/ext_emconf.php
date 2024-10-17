<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'This extension contains dependency injection fixtures.',
    'description' => 'This extension contains dependency injection fixture.',
    'category' => 'example',
    'version' => '14.0.0',
    'state' => 'beta',
    'author' => 'Benjamin Franzke',
    'author_email' => 'ben@bnf.dev',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
