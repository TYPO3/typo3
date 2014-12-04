<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'tools_txtaskcenterM1',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'task/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'user',
		'task',
		'top',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'task/',
		array(
			'script' => '_DISPATCH',
			'access' => 'group,user',
			'name' => 'user_task',
			'labels' => array(
				'tabs_images' => array(
					'tab' => '../Resources/Public/Icons/module-taskcenter.png',
				),
				'll_ref' => 'LLL:EXT:taskcenter/task/locallang_mod.xlf',
			),
		)
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
		'Taskcenter::saveCollapseState',
		\TYPO3\CMS\Taskcenter\TaskStatus::class . '->saveCollapseState'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
		'Taskcenter::saveSortingState',
		\TYPO3\CMS\Taskcenter\TaskStatus::class . '->saveSortingState'
	);
}
