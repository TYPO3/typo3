<?php

/**
 * Definitions for routes provided by EXT:impexp
 */
return [
    // Register click menu entry point
    'tx_impexp_export' => [
        'path' => '/record/importexport/export',
        'target' => \TYPO3\CMS\Impexp\Controller\ExportController::class . '::mainAction',
    ],
    'tx_impexp_import' => [
        'path' => '/record/importexport/import',
        'target' => \TYPO3\CMS\Impexp\Controller\ImportController::class . '::mainAction',
    ],
];
