<?php

/**
 * Addons for t3editor
 */
return [
    'dialog/dialog' => [
        'module' => 'cm/addon/dialog/dialog',
        'cssFiles' => [
            'EXT:t3editor/Resources/Public/JavaScript/Contrib/cm/addon/dialog/dialog.css',
        ],
    ],
    'display/fullscreen' => [
        'module' => 'cm/addon/display/fullscreen',
        'cssFiles' => [
            'EXT:t3editor/Resources/Public/JavaScript/Contrib/cm/addon/display/fullscreen.css',
        ],
    ],
    'display/autorefresh' => [
        'module' => 'cm/addon/display/autorefresh',
    ],
    'display/panel' => [
        'module' => 'cm/addon/display/panel',
    ],
    'fold/xml-fold' => [
        'module' => 'cm/addon/fold/xml-fold',
    ],
    'scroll/simplescrollbars' => [
        'module' => 'cm/addon/scroll/simplescrollbars',
        'cssFiles' => [
            'EXT:t3editor/Resources/Public/JavaScript/Contrib/cm/addon/scroll/simplescrollbars.css',
        ],
        'options' => [
            'scrollbarStyle' => 'simple',
        ],
    ],
    'scroll/annotatescrollbar' => [
        'module' => 'cm/addon/scroll/annotatescrollbar',
    ],
    'search/searchcursor' => [
        'module' => 'cm/addon/search/searchcursor',
    ],
    'search/search' => [
        'module' => 'cm/addon/search/search',
    ],
    'search/jump-to-line' => [
        'module' => 'cm/addon/search/jump-to-line',
    ],
    'search/matchesonscrollbar' => [
        'module' => 'cm/addon/search/matchesonscrollbar',
        'cssFiles' => [
            'EXT:t3editor/Resources/Public/JavaScript/Contrib/cm/addon/search/matchesonscrollbar.css',
        ],
    ],
    'edit/matchbrackets' => [
        'module' => 'cm/addon/edit/matchbrackets',
        'options' => [
            'matchBrackets' => true,
        ],
    ],
    'edit/closebrackets' => [
        'module' => 'cm/addon/edit/closebrackets',
        'options' => [
            'autoCloseBrackets' => true,
        ],
    ],
    'selection/active-line' => [
        'module' => 'cm/addon/selection/active-line',
        'options' => [
            'styleActiveLine' => true,
        ],
    ],
    'edit/matchtags' => [
        'module' => 'cm/addon/edit/matchtags',
        'options' => [
            'matchTags' => true,
        ],
    ],
    'edit/closetag' => [
        'module' => 'cm/addon/edit/closetag',
        'options' => [
            'autoCloseTags' => true,
        ],
    ],
    'hint/show-hint' => [
        'module' => 'cm/addon/hint/show-hint',
        'cssFiles' => [
            'EXT:t3editor/Resources/Public/JavaScript/Contrib/cm/addon/hint/show-hint.css',
        ],
        'options' => [
            'hintOptions' => [
                'completeSingle' => false,
            ],
        ],
    ],
    'hint/css-hint' => [
        'module' => 'cm/addon/hint/css-hint',
        'modes' => ['css'],
    ],
    'hint/xml-hint' => [
        'module' => 'cm/addon/hint/xml-hint',
        'modes' => ['htmlmixed', 'xml'],
    ],
    'hint/html-hint' => [
        'module' => 'cm/addon/hint/html-hint',
        'modes' => ['htmlmixed'],
    ],
    'hint/javascript-hint' => [
        'module' => 'cm/addon/hint/javascript-hint',
        'modes' => ['javascript'],
    ],
    'hint/sql-hint' => [
        'module' => 'cm/addon/hint/sql-hint',
        'modes' => ['sql'],
    ],
    'hint/typoscript-hint' => [
        'module' => 'TYPO3/CMS/T3editor/Addon/Hint/TypoScriptHint',
        'modes' => ['typoscript'],
    ],
];
