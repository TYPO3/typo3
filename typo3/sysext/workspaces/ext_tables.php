<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Registers the workspaces Backend Module
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'web',
    'WorkspacesWorkspaces',
    'before:info',
    null,
    [
        'routeTarget' => \TYPO3\CMS\Workspaces\Controller\ReviewController::class . '::indexAction',
        'access' => 'user,group',
        'name' => 'web_WorkspacesWorkspaces',
        'icon' => 'EXT:workspaces/Resources/Public/Icons/module-workspaces.svg',
        'labels' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf',
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('sys_workspace_stage', 'EXT:workspaces/Resources/Private/Language/locallang_csh_sysws_stage.xlf');
