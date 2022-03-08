<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'RequestMirror',
    'description' => 'RequestMirror',
    'category' => 'example',
    'version' => '11.5.9',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.9',
            'frontend' => '11.5.9',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
