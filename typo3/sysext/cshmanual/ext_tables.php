<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModule('help', 'cshmanual', 'top', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'mod/');
}
?>