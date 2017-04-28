<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418731] = \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider::class;
$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418732] = \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FilemountsProvider::class;
$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418733] = \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileStorageProvider::class;
$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418734] = \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileDragProvider::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \TYPO3\CMS\Filelist\Hook\BackendControllerHook::class . '->addJavaScript';
