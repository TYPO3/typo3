<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Test with uuid fields',
    'description' => 'TYPO3 extension to be used for functional tests in TYPO3 core',
    'category' => 'example',
    'version' => '13.4.15',
    'state' => 'beta',
    'author' => 'Oliver Bartsch',
    'author_email' => 'bo@cedev.de',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.15',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
