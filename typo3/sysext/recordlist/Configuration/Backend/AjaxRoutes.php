<?php
/**
 * Definitions of routes
 */
return [
    'web_list_clearpagecache' => [
        'path' => '/web/list/clearpagecache',
        'target' => \TYPO3\CMS\Recordlist\Controller\ClearPageCacheController::class . '::mainAction',
    ],
    'record_download_settings' => [
        'path' => '/record/download/settings',
        'target' => \TYPO3\CMS\Recordlist\Controller\RecordDownloadController::class . '::downloadSettingsAction',
    ],
];
