<?php

declare(strict_types=1);

use TYPO3\CMS\Backend\Controller\AboutController;
use TYPO3\CMS\Backend\Controller\HelpController;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Controller\SiteConfigurationController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Register as a skin
$GLOBALS['TBE_STYLES']['skins']['backend']['stylesheetDirectories']['css'] = 'EXT:backend/Resources/Public/Css/';

ExtensionManagementUtility::addModule(
    'web',
    'layout',
    'top',
    '',
    [
        'routeTarget' => PageLayoutController::class . '::mainAction',
        'access' => 'user,group',
        'name' => 'web_layout',
        'iconIdentifier' => 'module-page',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf',
    ]
);

ExtensionManagementUtility::addModule(
    'site',
    'configuration',
    'top',
    '',
    [
        'routeTarget' => SiteConfigurationController::class . '::handleRequest',
        'access' => 'admin',
        'name' => 'site_configuration',
        'iconIdentifier' => 'module-sites',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_module.xlf',
    ]
);

// "Sort sub pages" csh
ExtensionManagementUtility::addLLrefForTCAdescr(
    'pages_sort',
    'EXT:backend/Resources/Private/Language/locallang_pages_sort_csh.xlf'
);
// "Create multiple pages" csh
ExtensionManagementUtility::addLLrefForTCAdescr(
    'pages_new',
    'EXT:backend/Resources/Private/Language/locallang_pages_new_csh.xlf'
);

// Csh manual
ExtensionManagementUtility::addModule(
    'help',
    'cshmanual',
    'top',
    '',
    [
        'routeTarget' => HelpController::class . '::handleRequest',
        'name' => 'help_cshmanual',
        'access' => 'user,group',
        'iconIdentifier' => 'module-cshmanual',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_mod_help_cshmanual.xlf',
    ]
);

ExtensionManagementUtility::addModule(
    'help',
    'AboutAbout',
    'top',
    null,
    [
        'routeTarget' => AboutController::class . '::indexAction',
        'access' => 'user,group',
        'name' => 'help_AboutAbout',
        'iconIdentifier' => 'module-about',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/Modules/about.xlf',
    ]
);

// Register the folder tree core navigation component
ExtensionManagementUtility::addCoreNavigationComponent('file', 'TYPO3/CMS/Backend/Tree/FileStorageTreeContainer');
