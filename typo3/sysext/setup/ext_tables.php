<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Initialize empty structure for backward compatibility with extensions
// that add fields via $GLOBALS['TYPO3_USER_SETTINGS']['columns'].
// Core settings are now defined in Configuration/TCA/Overrides/be_users.php.
// Access to settings should go through UserSettingsSchema which merges both sources.
$GLOBALS['TYPO3_USER_SETTINGS'] = [
    'columns' => [],
    'showitem' => '',
];
