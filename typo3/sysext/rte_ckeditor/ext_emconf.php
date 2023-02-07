<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS RTE CKEditor',
    'description' => 'Integration of CKEditor as a Rich Text Editor for the TYPO3 backend.',
    'category' => 'be',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '10.4.35',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.35',
        ],
        'conflicts' => [],
        'suggests' => [
            'setup' => '10.4.35',
        ],
    ],
];
