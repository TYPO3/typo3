<?php

/**
 * Definitions for routes provided by EXT:t3editor
 */
return [
    // Get TSRef
    't3editor_tsref' => [
        'path' => '/t3editor/tsref',
        'target' => \TYPO3\CMS\T3editor\Controller\TypoScriptReferenceController::class . '::loadReference'
    ],

    // Load code completion templates
    't3editor_codecompletion_loadtemplates' => [
        'path' => '/t3editor/codecompletion/load-templates',
        'target' => \TYPO3\CMS\T3editor\Controller\CodeCompletionController::class . '::loadCompletions'
    ]
];
