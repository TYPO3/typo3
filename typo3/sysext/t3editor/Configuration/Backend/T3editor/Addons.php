<?php

/**
 * Addons for t3editor
 */
return [
    'dialog/dialog' => [
        'module' => 'codemirror/addon/dialog/dialog',
        'cssFiles' => [
            'EXT:t3editor/Resources/Public/JavaScript/Contrib/codemirror/addon/dialog/dialog.css',
        ],
    ],
    'display/fullscreen' => [
        'module' => 'codemirror/addon/display/fullscreen',
        'cssFiles' => [
            'EXT:t3editor/Resources/Public/JavaScript/Contrib/codemirror/addon/display/fullscreen.css',
        ],
    ],
    'display/autorefresh' => [
        'module' => 'codemirror/addon/display/autorefresh',
    ],
    'display/panel' => [
        'module' => 'codemirror/addon/display/panel',
    ],
    'fold/xml-fold' => [
        'module' => 'codemirror/addon/fold/xml-fold',
    ],
    'scroll/simplescrollbars' => [
        'module' => 'codemirror/addon/scroll/simplescrollbars',
        'cssFiles' => [
            'EXT:t3editor/Resources/Public/JavaScript/Contrib/codemirror/addon/scroll/simplescrollbars.css',
        ],
        'options' => [
            'scrollbarStyle' => 'simple',
        ],
    ],
    'scroll/annotatescrollbar' => [
        'module' => 'codemirror/addon/scroll/annotatescrollbar',
    ],
    'search/searchcursor' => [
        'module' => 'codemirror/addon/search/searchcursor',
    ],
    'search/search' => [
        'module' => 'codemirror/addon/search/search',
    ],
    'search/jump-to-line' => [
        'module' => 'codemirror/addon/search/jump-to-line',
    ],
    'search/matchesonscrollbar' => [
        'module' => 'codemirror/addon/search/matchesonscrollbar',
        'cssFiles' => [
            'EXT:t3editor/Resources/Public/JavaScript/Contrib/codemirror/addon/search/matchesonscrollbar.css',
        ],
    ],
    'edit/matchbrackets' => [
        'module' => 'codemirror/addon/edit/matchbrackets',
        'options' => [
            'matchBrackets' => true,
        ],
    ],
    'edit/closebrackets' => [
        'module' => 'codemirror/addon/edit/closebrackets',
        'options' => [
            'autoCloseBrackets' => true,
        ],
    ],
    'selection/active-line' => [
        'module' => 'codemirror/addon/selection/active-line',
        'options' => [
            'styleActiveLine' => true,
        ],
    ],
    'edit/matchtags' => [
        'module' => 'codemirror/addon/edit/matchtags',
        'options' => [
            'matchTags' => true,
        ],
    ],
    'edit/closetag' => [
        'module' => 'codemirror/addon/edit/closetag',
        'options' => [
            'autoCloseTags' => true,
        ],
    ],
    'hint/show-hint' => [
        'module' => 'codemirror/addon/hint/show-hint',
        'cssFiles' => [
            'EXT:t3editor/Resources/Public/JavaScript/Contrib/codemirror/addon/hint/show-hint.css',
        ],
        'options' => [
            'hintOptions' => [
                'completeSingle' => false,
            ],
        ],
    ],
    'hint/css-hint' => [
        'module' => 'codemirror/addon/hint/css-hint',
        'modes' => ['css'],
    ],
    'hint/xml-hint' => [
        'module' => 'codemirror/addon/hint/xml-hint',
        'modes' => ['htmlmixed', 'xml'],
    ],
    'hint/html-hint' => [
        'module' => 'codemirror/addon/hint/html-hint',
        'modes' => ['htmlmixed'],
    ],
    'hint/javascript-hint' => [
        'module' => 'codemirror/addon/hint/javascript-hint',
        'modes' => ['javascript'],
    ],
    'hint/sql-hint' => [
        'module' => 'codemirror/addon/hint/sql-hint',
        'modes' => ['sql'],
    ],
    'hint/typoscript-hint' => [
        'module' => 'TYPO3/CMS/T3editor/Addon/Hint/TypoScriptHint',
        'modes' => ['typoscript'],
    ],
];
