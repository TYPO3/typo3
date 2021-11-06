<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Info\Controller\InfoModuleController;
use TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController;
use TYPO3\CMS\Info\Controller\PageInformationController;
use TYPO3\CMS\Info\Controller\TranslationStatusController;

defined('TYPO3') or die();

ExtensionManagementUtility::addModule(
    'web',
    'info',
    '',
    '',
    [
        'routeTarget' => InfoModuleController::class . '::mainAction',
        'access' => 'user,group',
        'name' => 'web_info',
        'iconIdentifier' => 'module-info',
        'labels' => 'LLL:EXT:info/Resources/Private/Language/locallang_mod_web_info.xlf',
    ]
);
ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_info', 'EXT:info/Resources/Private/Language/locallang_csh_web_info.xlf');
ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_infotsconfig', 'EXT:info/Resources/Private/Language/locallang_csh_tsconfigInfo.xlf');

ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    PageInformationController::class,
    '',
    'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_page'
);
ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    TranslationStatusController::class,
    '',
    'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_lang'
);
ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    InfoPageTyposcriptConfigController::class,
    '',
    'LLL:EXT:info/Resources/Private/Language/InfoPageTsConfig.xlf:mod_pagetsconfig'
);
