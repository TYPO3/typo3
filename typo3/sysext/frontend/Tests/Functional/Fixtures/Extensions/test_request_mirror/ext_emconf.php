<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'RequestMirror',
    'description' => 'RequestMirror',
    'category' => 'example',
    'version' => '13.0.1',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.1',
            'frontend' => '13.0.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
