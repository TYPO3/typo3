<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
\TYPO3\CMS\Core\Extension\ExtensionManager::addPageTSConfig(((('
	RTE.default.skin = EXT:' . $_EXTKEY) . '/rtehtmlarea/htmlarea.css
	RTE.default.FE.skin = EXT:') . $_EXTKEY) . '/rtehtmlarea/htmlarea.css
');
?>