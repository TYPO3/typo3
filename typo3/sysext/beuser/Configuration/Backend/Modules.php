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
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'labels' => 'beuser.modules.permissions',
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
        'path' => '/module/system/user-management',
        'iconIdentifier' => 'module-beuser',
        'labels' => 'beuser.modules.user_management',
        'aliases' => ['system_BeuserTxBeuser'],
        'extensionName' => 'Beuser',
        'controllerActions' => [
            BackendUserController::class => [
                'index',
                'list',
                'show',
                'addToCompareList',
                'removeFromCompareList',
                'removeAllFromCompareList',
                'compare',
                'online',
                'terminateBackendUserSession',
                'initiatePasswordReset',
                'groups',
                'showGroup',
                'addGroupToCompareList',
                'removeGroupFromCompareList',
                'removeAllGroupsFromCompareList',
                'compareGroups',
                'filemounts',
            ],
        ],
    ],
];
