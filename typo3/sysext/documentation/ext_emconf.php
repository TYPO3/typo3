<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Documentation',
    'description' => 'Backend module for TYPO3 to list and show documentation of loaded extensions as well as custom documents.',
    'category' => 'be',
    'author' => 'Xavier Perseguers, Francois Suter',
    'author_email' => 'xavier@typo3.org, francois.suter@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'uploadfolder' => '0',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '7.6.0',
    'constraints' => [
        'depends' => [
            'extbase' => '7.6.0-7.6.99',
            'fluid' => '7.6.0-7.6.99',
            'typo3' => '7.6.0-7.6.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
