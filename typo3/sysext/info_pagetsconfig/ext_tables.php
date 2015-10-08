<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_info',
        \TYPO3\CMS\InfoPagetsconfig\Controller\InfoPageTyposcriptConfigController::class,
        null,
        'LLL:EXT:info_pagetsconfig/Resources/Private/Language/locallang.xlf:mod_pagetsconfig'
    );
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_info', 'EXT:info_pagetsconfig/Resources/Private/Language/locallang_csh_webinfo.xlf');
