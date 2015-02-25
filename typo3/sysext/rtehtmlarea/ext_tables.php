<?php
defined('TYPO3_MODE') or die();

// Add static template for Click-enlarge rendering
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('rtehtmlarea', 'static/clickenlarge/', 'Clickenlarge Rendering');

// Add Abbreviation records (as of 7.0 not working in Configuration/TCA/Overrides)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_rtehtmlarea_acronym');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_rtehtmlarea_acronym', 'EXT:rtehtmlarea/locallang_csh_abbreviation.xlf');

// Add contextual help files
$htmlAreaRteContextHelpFiles = array(
	'General' => 'EXT:rtehtmlarea/locallang_csh.xlf',
	'Abbreviation' => 'EXT:rtehtmlarea/extensions/Abbreviation/locallang_csh.xlf',
	'EditElement' => 'EXT:rtehtmlarea/extensions/EditElement/locallang_csh.xlf',
	'Language' => 'EXT:rtehtmlarea/extensions/Language/locallang_csh.xlf',
	'MicrodataSchema' => 'EXT:rtehtmlarea/extensions/MicrodataSchema/locallang_csh.xlf',
	'PlainText' => 'EXT:rtehtmlarea/extensions/PlainText/locallang_csh.xlf',
	'RemoveFormat' => 'EXT:rtehtmlarea/extensions/RemoveFormat/locallang_csh.xlf',
	'TableOperations' => 'EXT:rtehtmlarea/extensions/TableOperations/locallang_csh.xlf'
);
foreach ($htmlAreaRteContextHelpFiles as $key => $file) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('xEXT_rtehtmlarea_' . $key, $file);
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
	// Register RTE browse links wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'rtehtmlarea_wizard_browse_links',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/BrowseLinks/'
	);

	// Register RTE select image wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'rtehtmlarea_wizard_select_image',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/SelectImage/'
	);

	// Register RTE user elements wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'rtehtmlarea_wizard_user_elements',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/UserElements/'
	);

	// Register RTE parse html wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'rtehtmlarea_wizard_parse_html',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/ParseHtml/'
	);
}
