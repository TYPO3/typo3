<?php

/**
 * Definitions for routes provided by EXT:impexp
 */
return [
    // Register click menu entry point
    /**
     * @deprecated since TYPO3 v10.0, will be removed in TYPO3 v11.0.
     */
    'xMOD_tximpexp' => [
        'path' => '/record/importexport/',
        'target' => \TYPO3\CMS\Impexp\Controller\ImportExportController::class . '::mainAction'
    ],
    'tx_impexp_export' => [
        'path' => '/record/importexport/export',
        'target' => \TYPO3\CMS\Impexp\Controller\ExportController::class . '::mainAction'
    ],
    'tx_impexp_import' => [
        'path' => '/record/importexport/import',
        'target' => \TYPO3\CMS\Impexp\Controller\ImportController::class . '::mainAction'
    ],
];
