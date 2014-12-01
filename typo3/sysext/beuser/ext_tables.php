<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	// Module System > Backend Users
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'system',
		'tx_Beuser',
		'top',
		array(
			'BackendUser' => 'index, addToCompareList, removeFromCompareList, compare, online, terminateBackendUserSession'
		),
		array(
			'access' => 'admin',
			'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-beuser.png',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf'
		)
	);

	// Module System > Access
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'system',
		'tx_Permission',
		'top',
		array(
			'Permission' => 'index, edit, update'
		),
		array(
			'access' => 'admin',
			'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-permission.png',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_permission.xlf',
			'navigationComponentId' => 'typo3-pagetree'
		)
	);

	// Register AJAX Controller
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('PermissionAjaxController::dispatch',
		\TYPO3\CMS\Beuser\Controller\PermissionAjaxController::class . '->dispatch');
}
