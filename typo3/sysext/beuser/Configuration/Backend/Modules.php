<?php

use TYPO3\CMS\Beuser\Controller\BackendUserController;
use TYPO3\CMS\Beuser\Controller\PermissionController;

/**
 * Definitions for modules provided by EXT:beuser
 */
return [
    'permissions_pages' => [
        'parent' => 'system',
        'position' => ['before' => '*'],
        'access' => 'admin',
        'path' => '/module/system/permissions',
        'iconIdentifier' => 'module-permission',
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
        'labels' => 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf',
        'aliases' => ['system_BeuserTxPermission'],
        'routes' => [
            '_default' => [
                'target' => PermissionController::class . '::handleRequest',
            ],
        ],
    ],
    'backend_user_management' => [
        'parent' => 'system',
        'position' => ['after' => 'permissions_pages'],
        'access' => 'admin',
        'iconIdentifier' => 'module-beuser',
        'labels' => 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Beuser',
        'aliases' => ['system_BeuserTxBeuser'],
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
                'filemounts',
            ],
        ],
    ],
];
