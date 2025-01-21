<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'This extension contains site set fixtures.',
    'description' => 'This extension contains site set fixtures.',
    'category' => 'example',
    'version' => '13.4.5',
    'state' => 'stable',
    'author' => 'Benjamin Franzke',
    'author_email' => 'ben@bnf.dev',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.5',
            'frontend' => '13.4.5',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
