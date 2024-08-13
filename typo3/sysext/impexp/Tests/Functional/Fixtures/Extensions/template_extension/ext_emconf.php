<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Impexp test extension',
    'description' => '',
    'category' => '',
    'version' => '11.5.40',
    'state' => 'beta',
    'clearCacheOnLoad' => 1,
    'author' => 'Marc Bastian Heinrichs',
    'author_email' => 'typo3@mbh-software.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.40',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
