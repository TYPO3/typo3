<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Extension\ExtensionManager::insertModuleFunction('web_ts', 'tx_tstemplateinfo', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'class.tx_tstemplateinfo.php', 'LLL:EXT:tstemplate/ts/locallang.xml:infoModify');
}
?>