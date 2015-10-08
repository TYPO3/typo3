<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    // Add module
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_info',
        \TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport::class,
        null,
        'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:mod_linkvalidator'
    );
}
// Initialize Context Sensitive Help (CSH)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'linkvalidator',
    'EXT:linkvalidator/Resources/Private/Language/Module/locallang_csh.xlf'
);
