<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::registerPlugin(
    'test_startingpoint',
    'FrameworkStoragePid',
    'StoragePid probe (plain text)'
);
ExtensionUtility::registerPlugin(
    'test_startingpoint',
    'QuerySettingsStoragePid',
    'QuerySettings StoragePid probe (plain text)'
);
