<?php
defined('TYPO3_MODE') or die();

// Registers the workspaces Backend Module
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Workspaces',
    'web',
    'workspaces',
    'before:info',
    [
        // An array holding the controller-action-combinations that are accessible
        \TYPO3\CMS\Workspaces\Controller\ReviewController::class => 'index,fullIndex,singleIndex'
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:workspaces/Resources/Public/Icons/module-workspaces.svg',
        'labels' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf',
        'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement'
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('sys_workspace_stage', 'EXT:workspaces/Resources/Private/Language/locallang_csh_sysws_stage.xlf');
