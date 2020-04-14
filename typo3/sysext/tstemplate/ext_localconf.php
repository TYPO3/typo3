<?php

defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['tstemplate'] = \TYPO3\CMS\Tstemplate\Hooks\DataHandlerClearCachePostProcHook::class . '->clearPageCacheIfNecessary';
