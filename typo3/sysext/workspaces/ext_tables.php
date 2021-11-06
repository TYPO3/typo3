<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Workspaces\Controller\ReviewController;

defined('TYPO3') or die();

// Registers the workspaces Backend Module
ExtensionManagementUtility::addModule(
    'web',
    'WorkspacesWorkspaces',
    'before:info',
    null,
    [
        'routeTarget' => ReviewController::class . '::indexAction',
        'access' => 'user,group',
        'name' => 'web_WorkspacesWorkspaces',
        'iconIdentifier' => 'module-workspaces',
        'labels' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf',
    ]
);

ExtensionManagementUtility::addLLrefForTCAdescr('sys_workspace_stage', 'EXT:workspaces/Resources/Private/Language/locallang_csh_sysws_stage.xlf');
