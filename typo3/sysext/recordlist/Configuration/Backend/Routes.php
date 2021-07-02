<?php
/**
 * Definitions of routes
 */
return [
    // Register wizard element browser
    'wizard_element_browser' => [
        'path' => '/wizard/record/browse',
        'target' => \TYPO3\CMS\Recordlist\Controller\ElementBrowserController::class . '::mainAction'
    ],
    'record_export' => [
        'path' => '/record/export',
        'methods' => ['POST'],
        'target' => \TYPO3\CMS\Recordlist\Controller\RecordExportController::class . '::handleExportRequest'
    ],
];
