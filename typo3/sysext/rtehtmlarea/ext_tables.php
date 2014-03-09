<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Add static template for Click-enlarge rendering
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'static/clickenlarge/', 'Clickenlarge Rendering');
// Add configuration of soft references on image tags in RTE content
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'hooks/softref/ext_tables.php';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_rtehtmlarea_acronym');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_rtehtmlarea_acronym', 'EXT:' . $_EXTKEY . '/locallang_csh_abbreviation.xlf');
// Add contextual help files
$htmlAreaRteContextHelpFiles = array(
	'General' => 'EXT:' . $_EXTKEY . '/locallang_csh.xlf',
	'Acronym' => 'EXT:' . $_EXTKEY . '/extensions/Acronym/locallang_csh.xlf',
	'EditElement' => 'EXT:' . $_EXTKEY . '/extensions/EditElement/locallang_csh.xlf',
	'Language' => 'EXT:' . $_EXTKEY . '/extensions/Language/locallang_csh.xlf',
	'MicrodataSchema' => 'EXT:' . $_EXTKEY . '/extensions/MicrodataSchema/locallang_csh.xlf',
	'PlainText' => 'EXT:' . $_EXTKEY . '/extensions/PlainText/locallang_csh.xlf',
	'RemoveFormat' => 'EXT:' . $_EXTKEY . '/extensions/RemoveFormat/locallang_csh.xlf',
	'TableOperations' => 'EXT:' . $_EXTKEY . '/extensions/TableOperations/locallang_csh.xlf'
);
foreach ($htmlAreaRteContextHelpFiles as $key => $file) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('xEXT_' . $_EXTKEY . '_' . $key, $file);
}
unset($htmlAreaRteContextHelpFiles);
// Extend TYPO3 User Settings Configuration
if (TYPO3_MODE === 'BE' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('setup') && is_array($GLOBALS['TYPO3_USER_SETTINGS'])) {
	$GLOBALS['TYPO3_USER_SETTINGS']['columns'] = array_merge($GLOBALS['TYPO3_USER_SETTINGS']['columns'], array(
		'rteWidth' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:rtehtmlarea/locallang.xlf:rteWidth',
			'csh' => 'xEXT_rtehtmlarea_General:rteWidth'
		),
		'rteHeight' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:rtehtmlarea/locallang.xlf:rteHeight',
			'csh' => 'xEXT_rtehtmlarea_General:rteHeight'
		),
		'rteResize' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:rtehtmlarea/locallang.xlf:rteResize',
			'csh' => 'xEXT_rtehtmlarea_General:rteResize'
		),
		'rteMaxHeight' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:rtehtmlarea/locallang.xlf:rteMaxHeight',
			'csh' => 'xEXT_rtehtmlarea_General:rteMaxHeight'
		),
		'rteCleanPasteBehaviour' => array(
			'type' => 'select',
			'label' => 'LLL:EXT:rtehtmlarea/htmlarea/plugins/PlainText/locallang.xlf:rteCleanPasteBehaviour',
			'items' => array(
				'plainText' => 'LLL:EXT:rtehtmlarea/htmlarea/plugins/PlainText/locallang.xlf:plainText',
				'pasteStructure' => 'LLL:EXT:rtehtmlarea/htmlarea/plugins/PlainText/locallang.xlf:pasteStructure',
				'pasteFormat' => 'LLL:EXT:rtehtmlarea/htmlarea/plugins/PlainText/locallang.xlf:pasteFormat'
			),
			'csh' => 'xEXT_rtehtmlarea_PlainText:behaviour'
		)
	));
	$GLOBALS['TYPO3_USER_SETTINGS']['showitem'] .= ',--div--;LLL:EXT:rtehtmlarea/locallang.xlf:rteSettings,rteWidth,rteHeight,rteResize,rteMaxHeight,rteCleanPasteBehaviour';
}
if (TYPO3_MODE === 'BE') {
	// Register RTE element browser wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'rtehtmlarea_wizard_element_browser',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod3/'
	);

	// Register RTE wizard_select_image
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'rtehtmlarea_wizard_select_image',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod4/'
	);

	// Register RTE wizard_user
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'rtehtmlarea_wizard_user',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod5/'
	);

	// Register RTE wizard_user
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'rtehtmlarea_wizard_parse_html',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod6/'
	);
}