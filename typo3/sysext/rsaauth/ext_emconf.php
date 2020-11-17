<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'RSA authentication for TYPO3',
    'description' => 'Contains a service to authenticate TYPO3 BE and FE users using private/public key encryption of passwords. The extension is deprecated. Please use https://',
    'category' => 'services',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'deprecated',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '9.5.24',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.24',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
