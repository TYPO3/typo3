<?php

use TYPO3\CMS\Beuser\Controller\BackendUserController;
use TYPO3\CMS\Beuser\Controller\PermissionController;

/**
 * Definitions for modules provided by EXT:beuser
 */
return [
    'system_BeuserTxPermission' => [
        'parent' => 'system',
        'position' => ['top'],
        'access' => 'admin',
        'path' => '/module/system/permissions',
        'iconIdentifier' => 'module-permission',
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
        'labels' => 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf',
        'routes' => [
            '_default' => [
                'target' => PermissionController::class . '::handleRequest',
            ],
        ],
    ],
    'system_BeuserTxBeuser' => [
        'parent' => 'system',
        'position' => ['after' => 'system_BeuserTxPermission'],
        'access' => 'admin',
        'iconIdentifier' => 'module-beuser',
        'labels' => 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Beuser',
        'controllerActions' => [
            BackendUserController::class => [
                'index',
                'show',
                'addToCompareList',
                'removeFromCompareList',
                'removeAllFromCompareList',
                'compare',
                'online',
                'terminateBackendUserSession',
                'initiatePasswordReset',
                'groups',
                'addGroupToCompareList',
                'removeGroupFromCompareList',
                'removeAllGroupsFromCompareList',
                'compareGroups',
            ],
        ],
    ],
];
