<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'tools_txtaskcenterM1',
		'EXT:taskcenter/Modules/Taskcenter/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'user',
		'task',
		'top',
		'EXT:taskcenter/Modules/Taskcenter/',
		array(
			'script' => '_DISPATCH',
			'access' => 'group,user',
			'name' => 'user_task',
			'labels' => array(
				'tabs_images' => array(
					'tab' => 'EXT:taskcenter/Resources/Public/Icons/module-taskcenter.svg',
				),
				'll_ref' => 'LLL:EXT:taskcenter/Resources/Private/Language/locallang_mod.xlf',
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
