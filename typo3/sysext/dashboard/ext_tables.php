<?php

defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'dashboard',
    '',
    'top',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Dashboard\Controller\DashboardController::class . '::handleRequest',
        'access' => 'user,group',
        'name' => 'dashboard',
        'icon' => 'EXT:dashboard/Resources/Public/Icons/Extension.svg',
        'navigationComponentId' => '',
        'inheritNavigationComponentFromMainModule' => false,
        'labels' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang_mod.xlf',
        'standalone' => true
    ]
);

$GLOBALS['TBE_STYLES']['skins']['dashboard']['stylesheetDirectories']['modal'] = 'EXT:dashboard/Resources/Public/Css/Modal/';
