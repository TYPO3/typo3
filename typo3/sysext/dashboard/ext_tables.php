<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TBE_MODULES']['_configuration']['dashboard'] = [
    'labels' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang_mod.xlf',
    'name' => 'dashboard'
];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'web',
    'dashboard',
    'top',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Dashboard\Controller\DashboardController::class . '::handleRequest',
        'access' => 'user,group',
        'name' => 'web_dashboard',
        'icon' => 'EXT:dashboard/Resources/Public/Icons/Extension.svg',
        'navigationComponentId' => '',
        'inheritNavigationComponentFromMainModule' => false,
        'labels' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang_mod.xlf'
    ]
);

$GLOBALS['TBE_STYLES']['skins']['dashboard']['stylesheetDirectories']['modal'] = 'EXT:dashboard/Resources/Public/Css/Modal/';
