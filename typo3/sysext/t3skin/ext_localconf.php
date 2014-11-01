<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	RTE.default.skin = EXT:' . $_EXTKEY . '/rtehtmlarea/htmlarea.css
	RTE.default.FE.skin = EXT:' . $_EXTKEY . '/rtehtmlarea/htmlarea.css
');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook']['TYPO3\\CMS\\T3skin\\Hook\\StyleGenerationHook'] = 'TYPO3\\CMS\\T3skin\\Hook\\StyleGenerationHook->preStartPageHook';

\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')->connect(
	'TYPO3\\CMS\\Backend\\Utility\\IconUtility',
	'buildSpriteHtmlIconTag',
	'TYPO3\\CMS\\T3skin\\Slot\\IconStyleModifier',
	'buildSpriteHtmlIconTag'
);