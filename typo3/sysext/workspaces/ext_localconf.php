<?php

declare(strict_types=1);

use TYPO3\CMS\Workspaces\Hook\DataHandlerHook;
use TYPO3\CMS\Workspaces\Hook\DataHandlerInternalWorkspaceTablesHook;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['workspaces'] = DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['workspaces'] = DataHandlerHook::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['workspacesInternalTables'] = DataHandlerInternalWorkspaceTablesHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['workspacesInternalTables'] = DataHandlerInternalWorkspaceTablesHook::class;
