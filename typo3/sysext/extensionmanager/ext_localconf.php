<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Cache\Backend\FileBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;

defined('TYPO3') or die();

// Short-lived cache for responses of the TER REST API, so browsing and
// paginating the extension catalogue does not issue a request per interaction.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extensionmanager_ter_api'] ?? null)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extensionmanager_ter_api'] = [
        'frontend' => VariableFrontend::class,
        'backend' => FileBackend::class,
        'options' => [
            'defaultLifetime' => 3600,
        ],
        'groups' => ['system'],
    ];
}
