<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'DataHandler Test',
    'description' => 'DataHandler Test',
    'category' => 'example',
    'version' => '11.5.42',
    'state' => 'beta',
    'author' => 'Oliver Hader',
    'author_email' => 'oliver@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.42',
            'workspaces' => '11.5.42',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
