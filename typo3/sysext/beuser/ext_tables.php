<?php

defined('TYPO3') or die();

// Module System > Backend Users
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Beuser',
    'system',
    'tx_Beuser',
    'top',
    [
        \TYPO3\CMS\Beuser\Controller\BackendUserController::class => 'index, show, addToCompareList, removeFromCompareList, removeAllFromCompareList, compare, online, terminateBackendUserSession, initiatePasswordReset, groups, addGroupToCompareList, removeGroupFromCompareList, removeAllGroupsFromCompareList, compareGroups',
    ],
    [
        'access' => 'admin',
        'icon' => 'EXT:beuser/Resources/Public/Icons/module-beuser.svg',
        'labels' => 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf'
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'system',
    'BeuserTxPermission',
    'top',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Beuser\Controller\PermissionController::class . '::handleRequest',
        'name' => 'system_BeuserTxPermission',
        'access' => 'admin',
        'icon' => 'EXT:beuser/Resources/Public/Icons/module-permission.svg',
        'labels' => 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf',
        'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement'
    ]
);
