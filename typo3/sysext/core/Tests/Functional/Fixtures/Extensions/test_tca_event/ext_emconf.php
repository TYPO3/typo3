<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'This extension defines event listeners for TCA modification.',
    'description' => 'This extension defines event listeners for TCA modification.',
    'category' => 'example',
    'version' => '13.4.4',
    'state' => 'beta',
    'author' => 'Nikita Hovratov',
    'author_email' => 'info@nikita-hovratov.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.4',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
