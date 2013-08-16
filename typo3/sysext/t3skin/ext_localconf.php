<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	RTE.default.skin = EXT:' . $_EXTKEY . '/Resources/Public/Css/rtehtmlarea/htmlarea.css
	RTE.default.FE.skin = EXT:' . $_EXTKEY . '/Resources/Public/Css/rtehtmlarea/htmlarea.css
');
?>