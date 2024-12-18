<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'This extension contains site set fixtures.',
    'description' => 'This extension contains site set fixtures.',
    'category' => 'example',
    'version' => '14.0.0',
    'state' => 'stable',
    'author' => 'Benjamin Franzke',
    'author_email' => 'ben@bnf.dev',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0',
            'frontend' => '14.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
