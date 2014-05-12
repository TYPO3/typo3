<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Indexed Search (experimental)');

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'tools',
		'isearch',
		'after:log',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_info',
		\TYPO3\CMS\IndexedSearch\Controller\IndexedPagesController::class,
		NULL,
		'LLL:EXT:indexed_search/locallang.xlf:mod_indexed_search'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_info',
		\TYPO3\CMS\IndexedSearch\Controller\IndexingStatisticsController::class,
		NULL,
		'LLL:EXT:indexed_search/locallang.xlf:mod2_indexed_search'
	);

	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.IndexedSearch',
		'web',
		'isearch',
		'',
		array(
			'Administration' => 'index,pages,externalDocuments,statistic,statisticDetails,deleteIndexedItem,saveStopwordsKeywords,wordDetail',
		),
		array(
			'access' => 'admin',
			'icon'   => 'EXT:indexed_search/Resources/Public/Icons/module-indexed_search.png',
			'labels' => 'LLL:EXT:indexed_search/mod/locallang_mod.xlf',
		)
	);
}


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'IndexedSearch');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('index_config');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('index_config', 'EXT:indexed_search/locallang_csh_indexcfg.xlf');
