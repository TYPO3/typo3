<?php

/**
 * Definitions for routes provided by EXT:impexp
 */
return [
    // Register click menu entry point
    'xMOD_tximpexp' => [
        'path' => '/record/importexport/',
        'target' => \TYPO3\CMS\Impexp\Controller\ImportExportController::class . '::mainAction'
    ]
];
