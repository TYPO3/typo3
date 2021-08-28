<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport;

defined('TYPO3') or die();

// Add module
ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    LinkValidatorReport::class,
    '',
    'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:mod_linkvalidator'
);

// Initialize Context Sensitive Help (CSH)
ExtensionManagementUtility::addLLrefForTCAdescr(
    'linkvalidator',
    'EXT:linkvalidator/Resources/Private/Language/Module/locallang_csh.xlf'
);
