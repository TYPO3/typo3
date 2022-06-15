<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Editor with syntax highlighting',
    'description' => 'JavaScript-driven editor with syntax highlighting and code completion. Based on CodeMirror.',
    'category' => 'be',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '11.5.13',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.13',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
