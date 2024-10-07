<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Filelist',
    'description' => 'TYPO3 backend module (File>Filelist) used for managing files.',
    'category' => 'module',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '13.3.1',
    'constraints' => [
        'depends' => [
            'typo3' => '13.3.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
