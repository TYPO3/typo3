<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'list',
		'',
		'',
		array(
			'routeTarget' => \TYPO3\CMS\Recordlist\RecordList::class . '::mainAction',
			'access' => 'user,group',
			'name' => 'web_list',
			'labels' => array(
				'tabs_images' => array(
					'tab' => 'EXT:recordlist/Resources/Public/Icons/module-list.svg',
				),
				'll_ref' => 'LLL:EXT:lang/locallang_mod_web_list.xlf',
			),
		)
	);
}