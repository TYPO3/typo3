<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	// Register AJAX handlers:
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor::saveCode', \TYPO3\CMS\T3editor\T3editor::class . '->ajaxSaveCode');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor::getPlugins', \TYPO3\CMS\T3editor\T3editor::class . '->getPlugins');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor_TSrefLoader::getTypes', \TYPO3\CMS\T3editor\TypoScriptReferenceLoader::class . '->processAjaxRequest');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor_TSrefLoader::getDescription', \TYPO3\CMS\T3editor\TypoScriptReferenceLoader::class . '->processAjaxRequest');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('CodeCompletion::loadTemplates', \TYPO3\CMS\T3editor\CodeCompletion::class . '->processAjaxRequest');
}
