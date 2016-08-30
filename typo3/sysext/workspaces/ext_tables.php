<?php
defined('TYPO3_MODE') or die();

// avoid that this block is loaded in the frontend or within the upgrade-wizards
if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
    /** Registers a Backend Module */
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'TYPO3.CMS.Workspaces',
        'web',
        'workspaces',
        'before:info',
        [
            // An array holding the controller-action-combinations that are accessible
            'Review' => 'index,fullIndex,singleIndex',
            'Preview' => 'index,newPage'
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:workspaces/Resources/Public/Icons/module-workspaces.svg',
            'labels' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf',
            'navigationComponentId' => 'typo3-pagetree'
        ]
    );

    // register ExtDirect
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
        'TYPO3.Workspaces.ExtDirect',
        \TYPO3\CMS\Workspaces\ExtDirect\ExtDirectServer::class,
        'web_WorkspacesWorkspaces',
        'user,group'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
        'TYPO3.Workspaces.ExtDirectActions',
        \TYPO3\CMS\Workspaces\ExtDirect\ActionHandler::class,
        'web_WorkspacesWorkspaces',
        'user,group'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
        'TYPO3.Workspaces.ExtDirectMassActions',
        \TYPO3\CMS\Workspaces\ExtDirect\MassActionHandler::class,
        'web_WorkspacesWorkspaces',
        'user,group'
    );
}

// Registers preview link icon
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class)->registerIcon(
    'module-workspaces-action-preview-link',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:workspaces/Resources/Public/Images/generate-ws-preview-link.png']
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('sys_workspace_stage', 'EXT:workspaces/Resources/Private/Language/locallang_csh_sysws_stage.xlf');
