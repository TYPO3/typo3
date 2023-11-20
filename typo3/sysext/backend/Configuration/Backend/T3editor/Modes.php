<?php

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

/**
 * Mode definitions for code editor
 */
return [
    'css' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/lang-css', 'css')->invoke(),
        'extensions' => ['css'],
    ],
    'html' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/lang-html', 'html')->invoke(),
        'extensions' => ['htm', 'html'],
        'default' => true,
    ],
    'javascript' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/lang-javascript', 'javascript')->invoke(),
        'extensions' => ['javascript'],
    ],
    'json' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/lang-json', 'json')->invoke(),
        'extensions' => ['json'],
    ],
    'php' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/lang-php', 'php')->invoke(),
        'extensions' => ['php', 'php5', 'php7', 'phps'],
    ],
    'sql' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/lang-sql', 'sql')->invoke(),
        'extensions' => ['sql'],
    ],
    'typoscript' => [
        'module' => JavaScriptModuleInstruction::create('@typo3/backend/code-editor/language/typoscript.js', 'typoscript')->invoke(),
        'extensions' => ['ts', 'typoscript', 'tsconfig'],
    ],
    'xml' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/lang-xml', 'xml')->invoke(),
        'extensions' => ['xml'],
    ],
];
