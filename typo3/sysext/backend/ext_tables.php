<?php
defined('TYPO3_MODE') or die();

// Register as a skin
$GLOBALS['TBE_STYLES']['skins']['backend'] = [
    'name' => 'backend',
    'stylesheetDirectories' => [
        'css' => 'EXT:backend/Resources/Public/Css/'
    ]
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'web',
    'layout',
    'top',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Backend\Controller\PageLayoutController::class . '::mainAction',
        'access' => 'user,group',
        'name' => 'web_layout',
        'icon' => 'EXT:backend/Resources/Public/Icons/module-page.svg',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf'
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'site',
    'configuration',
    'top',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Backend\Controller\SiteConfigurationController::class . '::handleRequest',
        'access' => 'admin',
        'name' => 'site_configuration',
        'icon' => 'EXT:backend/Resources/Public/Icons/module-sites.svg',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_module.xlf'
    ]
);

// "Sort sub pages" csh
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'pages_sort',
    'EXT:backend/Resources/Private/Language/locallang_pages_sort_csh.xlf'
);
// "Create multiple pages" csh
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'pages_new',
    'EXT:backend/Resources/Private/Language/locallang_pages_new_csh.xlf'
);

// Csh manual
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'help',
    'cshmanual',
    'top',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Backend\Controller\HelpController::class . '::handleRequest',
        'name' => 'help_cshmanual',
        'access' => 'user,group',
        'icon' => 'EXT:backend/Resources/Public/Icons/module-cshmanual.svg',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_mod_help_cshmanual.xlf',
    ]
);
