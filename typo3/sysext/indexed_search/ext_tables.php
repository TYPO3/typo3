<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'tools',
		'isearch',
		'after:log',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_info',
		'TYPO3\\CMS\\IndexedSearch\\Controller\\IndexedPagesController',
		NULL,
		'LLL:EXT:indexed_search/locallang.xlf:mod_indexed_search'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_info',
		'TYPO3\\CMS\\IndexedSearch\\Controller\\IndexingStatisticsController',
		NULL,
		'LLL:EXT:indexed_search/locallang.xlf:mod2_indexed_search'
	);
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('index_config');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('index_config', 'EXT:indexed_search/locallang_csh_indexcfg.xlf');
