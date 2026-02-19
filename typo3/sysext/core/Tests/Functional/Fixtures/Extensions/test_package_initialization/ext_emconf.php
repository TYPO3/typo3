<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Testing PackageInitializationEvent',
    'description' => 'Testing PackageInitializationEvent',
    'category' => 'example',
    'version' => '14.1.1',
    'state' => 'beta',
    'author' => 'Oliver Bartsch',
    'author_email' => 'bo@cedev.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '14.1.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
