<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModule('web', 'ts', '', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'ts/');
}
?>