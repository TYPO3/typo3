<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	// Register AJAX handlers:
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor::saveCode', 'TYPO3\\CMS\\T3editor\\T3editor->ajaxSaveCode');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor::getPlugins', 'TYPO3\\CMS\\T3editor\\T3editor->getPlugins');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor_TSrefLoader::getTypes', 'TYPO3\\CMS\\T3editor\\TypoScriptReferenceLoader->processAjaxRequest');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor_TSrefLoader::getDescription', 'TYPO3\\CMS\\T3editor\\TypoScriptReferenceLoader->processAjaxRequest');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('CodeCompletion::loadTemplates', 'TYPO3\\CMS\\T3editor\\CodeCompletion->processAjaxRequest');
}
