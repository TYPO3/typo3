<?php

declare(strict_types=1);

use TYPO3\CMS\Workspaces\Hook\DataHandlerHook;

defined('TYPO3') or die();

// register the hook to actually do the work within DataHandler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['workspaces'] = DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['workspaces'] = DataHandlerHook::class;
