<?php

declare(strict_types=1);

use TYPO3\CMS\Beuser\Controller\BackendUserController;
use TYPO3\CMS\Beuser\Controller\PermissionController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

// Module System > Backend Users
ExtensionUtility::registerModule(
    'Beuser',
    'system',
    'tx_Beuser',
    'top',
    [
        BackendUserController::class => 'index, show, addToCompareList, removeFromCompareList, removeAllFromCompareList, compare, online, terminateBackendUserSession, initiatePasswordReset, groups, addGroupToCompareList, removeGroupFromCompareList, removeAllGroupsFromCompareList, compareGroups',
    ],
    [
        'access' => 'admin',
        'iconIdentifier' => 'module-beuser',
        'labels' => 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf',
    ]
);

ExtensionManagementUtility::addModule(
    'system',
    'BeuserTxPermission',
    'top',
    '',
    [
        'routeTarget' => PermissionController::class . '::handleRequest',
        'name' => 'system_BeuserTxPermission',
        'access' => 'admin',
        'iconIdentifier' => 'module-permission',
        'labels' => 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf',
        'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
    ]
);
