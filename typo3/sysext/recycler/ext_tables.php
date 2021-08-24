<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'web',
    // Legacy name, as this module was previously an Extbase controller. Keeping the name allows to keep the sys_be_shortcut functionality alive
    'RecyclerRecycler',
    '',
    null,
    [
        'routeTarget' => \TYPO3\CMS\Recycler\Controller\RecyclerModuleController::class . '::handleRequest',
        'access' => 'user,group',
        'workspaces' => 'online',
        'name' => 'web_RecyclerRecycler',
        'icon' => 'EXT:recycler/Resources/Public/Icons/module-recycler.svg',
        'labels' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_mod.xlf',
    ]
);
