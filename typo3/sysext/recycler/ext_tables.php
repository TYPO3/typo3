<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Recycler\Controller\RecyclerModuleController;

defined('TYPO3') or die();

ExtensionManagementUtility::addModule(
    'web',
    // Legacy name, as this module was previously an Extbase controller. Keeping the name allows to keep the sys_be_shortcut functionality alive
    'RecyclerRecycler',
    '',
    null,
    [
        'routeTarget' => RecyclerModuleController::class . '::handleRequest',
        'access' => 'user,group',
        'workspaces' => 'online',
        'name' => 'web_RecyclerRecycler',
        'iconIdentifier' => 'module-recycler',
        'labels' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_mod.xlf',
    ]
);
