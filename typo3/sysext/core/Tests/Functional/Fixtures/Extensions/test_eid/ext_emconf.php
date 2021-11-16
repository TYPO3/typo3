<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Frontend eid responder',
    'description' => 'Frontend eid responder',
    'category' => 'example',
    'version' => '11.5.4',
    'state' => 'beta',
    'clearCacheOnLoad' => 0,
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.4',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
