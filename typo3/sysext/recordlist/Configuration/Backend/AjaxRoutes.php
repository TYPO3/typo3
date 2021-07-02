<?php
/**
 * Definitions of routes
 */
return [
    'web_list_clearpagecache' => [
        'path' => '/web/list/clearpagecache',
        'target' => \TYPO3\CMS\Recordlist\Controller\ClearPageCacheController::class . '::mainAction'
    ],
    'record_export_settings' => [
        'path' => '/record/export/settings',
        'target' => \TYPO3\CMS\Recordlist\Controller\RecordExportController::class . '::exportSettingsAction'
    ],
    'record_show_columns' => [
        'path' => '/record/show/columns',
        'methods' => ['POST'],
        'target' => \TYPO3\CMS\Recordlist\Controller\ColumnSelectorController::class . '::updateVisibleColumnsAction'
    ],
    'record_show_columns_selector' => [
        'path' => '/record/show/columns/selector',
        'target' => \TYPO3\CMS\Recordlist\Controller\ColumnSelectorController::class . '::showColumnsSelectorAction'
    ],
];
