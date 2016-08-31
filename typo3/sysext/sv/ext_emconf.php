<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 System Services',
    'description' => 'The core/default services. This includes the default authentication services for now.',
    'category' => 'services',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Rene Fritz',
    'author_email' => 'r.fritz@colorcube.de',
    'author_company' => 'Colorcube',
    'version' => '8.4.0',
    'constraints' => [
        'depends' => [
            'typo3' => '8.4.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
