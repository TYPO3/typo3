<?php

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

/**
 * Addons for t3editor
 */
return [
    'highlightActiveLineGutter' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/view', 'highlightActiveLineGutter')->invoke(),
    ],
    'history' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/commands', 'history')->invoke(),
        'keymap' => JavaScriptModuleInstruction::create('@codemirror/commands', 'historyKeymap'),
    ],
    'foldGutter' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/language', 'foldGutter')->invoke(),
        'keymap' => JavaScriptModuleInstruction::create('@codemirror/language', 'foldKeymap'),
    ],
    'dropCursor' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/view', 'dropCursor')->invoke(),
    ],
    'indentOnInput' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/language', 'indentOnInput')->invoke(),
    ],
    'bracketMatching' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/language', 'bracketMatching')->invoke(),
    ],
    'closeBrackets' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/autocomplete', 'closeBrackets')->invoke(),
        'keymap' => JavaScriptModuleInstruction::create('@codemirror/autocomplete', 'closeBracketsKeymap'),
    ],
    'autocompletion' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/autocomplete', 'autocompletion')->invoke(),
        'keymap' => JavaScriptModuleInstruction::create('@codemirror/autocomplete', 'completionKeymap'),
    ],
    'rectangularSelection' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/view', 'rectangularSelection')->invoke(),
    ],
    'crosshairCursor' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/view', 'crosshairCursor')->invoke(),
    ],
    'highlightActiveLine' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/view', 'highlightActiveLine')->invoke(),
    ],
    'highlightSelectionMatches' => [
        'module' => JavaScriptModuleInstruction::create('@codemirror/search', 'highlightSelectionMatches')->invoke(),
        'keymap' => JavaScriptModuleInstruction::create('@codemirror/search', 'searchKeymap'),
    ],
    'lint' => [
        'keymap' => JavaScriptModuleInstruction::create('@codemirror/lint', 'lintKeymap'),
    ],
    /*
    'hint/typoscript-hint' => [
        'module' => 'TYPO3/CMS/T3editor/Addon/Hint/TypoScriptHint',
        'modes' => ['typoscript'],
    ],
    */
];
