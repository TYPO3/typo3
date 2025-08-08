<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'This extension contains an alternative View implementation and injects it into test_viewfactory_target',
    'description' => 'This extension contains an alternative View implementation and injects it into test_viewfactory_target',
    'category' => 'example',
    'version' => '14.0.0',
    'state' => 'beta',
    'author' => '',
    'author_email' => '',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0',
            'test_viewfactory_target' => '14.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
