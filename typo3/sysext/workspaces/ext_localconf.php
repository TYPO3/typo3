<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Workspaces\Backend\ToolbarItems\WorkspaceSelectorToolbarItem;
use TYPO3\CMS\Workspaces\Hook\BackendUtilityHook;
use TYPO3\CMS\Workspaces\Hook\DataHandlerHook;

defined('TYPO3') or die();

// add default notification options to every page
ExtensionManagementUtility::addPageTSConfig('
tx_workspaces.emails.layoutRootPaths.90 = EXT:workspaces/Resources/Private/Layouts/
tx_workspaces.emails.partialRootPaths.90 = EXT:workspaces/Resources/Private/Partials/
tx_workspaces.emails.templateRootPaths.90 = EXT:workspaces/Resources/Private/Templates/Email/
tx_workspaces.emails.format = html
');

// register the hook to actually do the work within DataHandler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['workspaces'] = DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['workspaces'] = DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['version'] = DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']['workspaces'] = BackendUtilityHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck']['workspaces'] = BackendUtilityHook::class . '->makeEditForm_accessCheck';

// Register workspaces cache if not already done in LocalConfiguration.php or a previously loaded extension.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['workspaces_cache'] ?? false)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['workspaces_cache'] = [
        'groups' => ['all'],
    ];
}

$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][1435433114] = WorkspaceSelectorToolbarItem::class;
