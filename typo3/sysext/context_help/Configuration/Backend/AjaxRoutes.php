<?php

/**
 * Definitions for routes provided by EXT:context_help
 */
return [
    // Fetch data about the context help
    'context_help' => [
        'path' => '/context-help',
        'target' => \TYPO3\CMS\ContextHelp\Controller\ContextHelpAjaxController::class . '::getHelpAction'
    ]
];
