<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'][] = \TYPO3\CMS\Beuser\Hook\SwitchBackUserHook::class . '->switchBack';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \TYPO3\CMS\Beuser\Hook\BackendControllerHook::class . '->addJavaScript';
$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418730] = \TYPO3\CMS\Beuser\ContextMenu\ItemProvider::class;
