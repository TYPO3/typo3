<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS T3Editor',
    'description' => 'JavaScript-driven editor with syntax highlighting and code completion. Based on CodeMirror.',
    'category' => 'be',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '12.3.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.3.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
