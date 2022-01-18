<?php

declare(strict_types=1);

use TYPO3\CMS\Belog\Module\BackendLogModuleBootstrap;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Module Web->Info->Log
ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    BackendLogModuleBootstrap::class,
    '',
    'Log'
);
