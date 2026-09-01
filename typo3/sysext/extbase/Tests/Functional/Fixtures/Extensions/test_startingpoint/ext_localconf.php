<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3Tests\TestStartingpoint\Controller\StoragePidProbeController;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'TestStartingpoint',
    'FrameworkStoragePid',
    [
        StoragePidProbeController::class => ['framework'],
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
ExtensionUtility::configurePlugin(
    'TestStartingpoint',
    'QuerySettingsStoragePid',
    [
        StoragePidProbeController::class => ['querysettings'],
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
