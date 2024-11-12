<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'RequestMirror',
    'description' => 'RequestMirror',
    'category' => 'example',
    'version' => '13.4.2',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.2',
            'frontend' => '13.4.2',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
