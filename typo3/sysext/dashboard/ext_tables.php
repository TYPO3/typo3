<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Dashboard\Controller\DashboardController;

defined('TYPO3') or die();

ExtensionManagementUtility::addModule(
    'dashboard',
    '',
    'top',
    '',
    [
        'routeTarget' => DashboardController::class . '::handleRequest',
        'access' => 'user,group',
        'name' => 'dashboard',
        'iconIdentifier' => 'module-dashboard',
        'navigationComponentId' => '',
        'inheritNavigationComponentFromMainModule' => false,
        'labels' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang_mod.xlf',
        'standalone' => true,
    ]
);

$GLOBALS['TBE_STYLES']['skins']['dashboard']['stylesheetDirectories']['modal'] = 'EXT:dashboard/Resources/Public/Css/Modal/';
