<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {

	/**
	 * Register "Styleguide" backend module
	 */
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.styleguide',
		'help',
		'styleguide',
		'',
		array(
			'Styleguide' => 'index, typography, trees, tables, buttons, forms, callout, flashMessages, tca, debug, helpers, icons, tab'
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:styleguide/Resources/Public/Icons/module.png',
			'labels' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang_styleguide.xlf',
		)
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_staticdata');
	// @todo: There is a nasty bug if one of those lines is missing, dataHandler will just not persist new rows, but gives no error message
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_inline_2_child1');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_inline_2_child2');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_inline_3_mm');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_inline_3_child');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_required_7_flex_inline_1_child1');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_required_8_child1');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_rte_3_child1');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_rte_4_flex_inline_1_child1');
}
