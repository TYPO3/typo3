<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'web',
    'info',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Info\Controller\InfoModuleController::class . '::mainAction',
        'access' => 'user,group',
        'name' => 'web_info',
        'icon' => 'EXT:info/Resources/Public/Icons/module-info.svg',
        'labels' => 'LLL:EXT:info/Resources/Private/Language/locallang_mod_web_info.xlf'
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_info', 'EXT:info/Resources/Private/Language/locallang_csh_web_info.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_infotsconfig', 'EXT:info/Resources/Private/Language/locallang_csh_tsconfigInfo.xlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    \TYPO3\CMS\Info\Controller\PageInformationController::class,
    null,
    'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_page'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    \TYPO3\CMS\Info\Controller\TranslationStatusController::class,
    null,
    'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_lang'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    \TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController::class,
    null,
    'LLL:EXT:info/Resources/Private/Language/InfoPageTsConfig.xlf:mod_pagetsconfig'
);
