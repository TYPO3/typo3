<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Testing FluidEmail',
    'description' => 'Testing FluidEmail',
    'category' => 'example',
    'version' => '12.0.0',
    'state' => 'beta',
    'clearCacheOnLoad' => 0,
    'author' => 'Oliver Bartsch',
    'author_email' => 'bo@cedev.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
