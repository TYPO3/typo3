<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Editor with syntax highlighting',
    'description' => 'JavaScript-driven editor with syntax highlighting and codecompletion. Based on CodeMirror.',
    'category' => 'be',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '10.4.4',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.4',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
