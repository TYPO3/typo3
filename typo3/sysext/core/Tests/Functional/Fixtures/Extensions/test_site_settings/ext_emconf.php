<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'This extension contains site settings fixtures.',
    'description' => 'This extension contains site settings fixtures.',
    'category' => 'example',
    'version' => '13.4.5',
    'state' => 'beta',
    'author' => 'Benjamin Franzke',
    'author_email' => 'ben@bnf.dev',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.5',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
