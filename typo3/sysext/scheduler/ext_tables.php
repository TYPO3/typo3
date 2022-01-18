<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Add context sensitive help (csh) to the backend module
ExtensionManagementUtility::addLLrefForTCAdescr(
    '_MOD_system_txschedulerM1',
    'EXT:scheduler/Resources/Private/Language/locallang_csh_scheduler.xlf'
);
