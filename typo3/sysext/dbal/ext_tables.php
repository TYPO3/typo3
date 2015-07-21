<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'tools',
		'txdbalM1',
		'',
		'EXT:dbal/Modules/Dbal/',
		array(
			'script' => '_DISPATCH',
			'access' => 'admin',
			'name' => 'tools_txdbalM1',
			'labels' => array(
				'tabs_images' => array(
					'tab' => 'EXT:dbal/Resources/Public/Icons/module-dbal.svg',
				),
				'll_ref' => 'LLL:EXT:dbal/Resources/Private/Language/locallang_mod.xlf',
			),
		)
	);
}
