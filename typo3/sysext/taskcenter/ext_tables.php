<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'user',
		'task',
		'top',
		'',
		array(
			'routeTarget' => \TYPO3\CMS\Taskcenter\Controller\TaskModuleController::class . '::mainAction',
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
