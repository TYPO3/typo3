<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Fluid Templating Engine',
    'description' => 'Fluid is a next-generation templating engine which makes the life of extension authors a lot easier!',
    'category' => 'fe',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '11.5.0',
    'constraints' => [
        'depends' => [
            'core' => '11.5.0',
            'extbase' => '11.5.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
