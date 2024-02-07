<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'This extension defines event listeners for page TSConfig modification.',
    'description' => 'This extension defines event listeners for page TSConfig modification.',
    'category' => 'example',
    'version' => '13.0.1',
    'state' => 'beta',
    'author' => 'Nikita Hovratov',
    'author_email' => 'info@nikita-hovratov.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
