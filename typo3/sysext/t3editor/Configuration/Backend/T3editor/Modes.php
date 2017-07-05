<?php

/**
 * Mode definitions for t3editor
 */
return [
    'css' => [
        'module' => 'cm/mode/css/css',
        'extensions' => ['css'],
    ],
    'html' => [
        'module' => 'cm/mode/htmlmixed/htmlmixed',
        'extensions' => ['htm', 'html'],
        'default' => true,
    ],
    'javascript' => [
        'module' => 'cm/mode/javascript/javascript',
        'extensions' => ['javascript'],
    ],
    'php' => [
        'module' => 'cm/mode/php/php',
        'extensions' => ['php', 'php5', 'php7', 'phps'],
    ],
    'typoscript' => [
        'module' => 'TYPO3/CMS/T3editor/Mode/typoscript/typoscript',
        'extensions' => ['ts', 'typoscript', 'tsconfig'],
    ],
    'xml' => [
        'module' => 'cm/mode/xml/xml',
        'extensions' => ['xml']
    ]
];
