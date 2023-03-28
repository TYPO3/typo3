<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Test TCA require in scoped environment (a)',
    'description' => 'Test TCA require in scoped environment (a)',
    'category' => 'example',
    'version' => '12.4.0',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
