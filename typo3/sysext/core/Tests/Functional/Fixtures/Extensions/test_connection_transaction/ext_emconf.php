<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Provides configuration to test connection transaction',
    'description' => 'Provides configuration to test connection transaction',
    'category' => 'example',
    'version' => '13.4.4',
    'state' => 'stable',
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.4',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
