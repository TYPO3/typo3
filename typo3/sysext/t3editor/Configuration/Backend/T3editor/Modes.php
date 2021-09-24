<?php

/**
 * Mode definitions for t3editor
 */
return [
    'css' => [
        'module' => 'codemirror/mode/css/css',
        'extensions' => ['css'],
    ],
    'html' => [
        'module' => 'codemirror/mode/htmlmixed/htmlmixed',
        'extensions' => ['htm', 'html'],
        'default' => true,
    ],
    'javascript' => [
        'module' => 'codemirror/mode/javascript/javascript',
        'extensions' => ['javascript'],
    ],
    'php' => [
        'module' => 'codemirror/mode/php/php',
        'extensions' => ['php', 'php5', 'php7', 'phps'],
    ],
    'typoscript' => [
        'module' => 'TYPO3/CMS/T3editor/Mode/typoscript/typoscript',
        'extensions' => ['ts', 'typoscript', 'tsconfig'],
    ],
    'xml' => [
        'module' => 'codemirror/mode/xml/xml',
        'extensions' => ['xml'],
    ],
];
