<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

		// Add static template for Click-enlarge rendering
	t3lib_extMgm::addStaticFile($_EXTKEY,'static/clickenlarge/','Clickenlarge Rendering');

		// Add acronyms table
	$TCA['tx_rtehtmlarea_acronym'] = Array (
		'ctrl' => Array (
			'title' => 'LLL:EXT:rtehtmlarea/locallang_db.xml:tx_rtehtmlarea_acronym',
			'label' => 'term',
			'default_sortby' => 'ORDER BY term',
			'sortby' => 'sorting',
			'delete' => 'deleted',
			'enablecolumns' => Array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
			),
			'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
			'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'extensions/Acronym/skin/images/acronym.gif',
		),
	);
	t3lib_extMgm::allowTableOnStandardPages('tx_rtehtmlarea_acronym');

		// Add contextual help files
	$htmlAreaRteContextHelpFiles = array(
		'General' => 'EXT:' . $_EXTKEY . '/locallang_csh.xml',
		'EditElement' => 'EXT:' . $_EXTKEY . '/extensions/EditElement/locallang_csh.xml',
		'Language' => 'EXT:' . $_EXTKEY . '/extensions/Language/locallang_csh.xml',
		'PlainText' => 'EXT:' . $_EXTKEY . '/extensions/PlainText/locallang_csh.xml',
		'RemoveFormat' => 'EXT:' . $_EXTKEY . '/extensions/RemoveFormat/locallang_csh.xml',
	);
	foreach ($htmlAreaRteContextHelpFiles as $key => $file) {
		t3lib_extMgm::addLLrefForTCAdescr('xEXT_' . $_EXTKEY . '_' . $key, $file);
	}
	unset($htmlAreaRteContextHelpFiles);

		// Extend TYPO3 User Settings Configuration
if (TYPO3_MODE === 'BE' && t3lib_extMgm::isLoaded('setup') && is_array($GLOBALS['TYPO3_USER_SETTINGS'])) {
	$GLOBALS['TYPO3_USER_SETTINGS']['columns'] = array_merge(
		$GLOBALS['TYPO3_USER_SETTINGS']['columns'],
		array(
			'rteWidth' => array(
				'type' => 'text',
				'label' => 'LLL:EXT:rtehtmlarea/locallang.xml:rteWidth',
				'csh' => 'xEXT_rtehtmlarea_General:rteWidth',
			),
			'rteHeight' => array(
				'type' => 'text',
				'label' => 'LLL:EXT:rtehtmlarea/locallang.xml:rteHeight',
				'csh' => 'xEXT_rtehtmlarea_General:rteHeight',
			),
			'rteResize' => array(
				'type' => 'check',
				'label' => 'LLL:EXT:rtehtmlarea/locallang.xml:rteResize',
				'csh' => 'xEXT_rtehtmlarea_General:rteResize',
			),
			'rteMaxHeight' => array(
				'type' => 'text',
				'label' => 'LLL:EXT:rtehtmlarea/locallang.xml:rteMaxHeight',
				'csh' => 'xEXT_rtehtmlarea_General:rteMaxHeight',
			),
			'rteCleanPasteBehaviour' => array(
				'type' => 'select',
				'label' => 'LLL:EXT:rtehtmlarea/htmlarea/plugins/PlainText/locallang.xml:rteCleanPasteBehaviour',
				'items' => array(
					'plainText' => 'LLL:EXT:rtehtmlarea/htmlarea/plugins/PlainText/locallang.xml:plainText',
					'pasteStructure' => 'LLL:EXT:rtehtmlarea/htmlarea/plugins/PlainText/locallang.xml:pasteStructure',
					'pasteFormat' => 'LLL:EXT:rtehtmlarea/htmlarea/plugins/PlainText/locallang.xml:pasteFormat',
				),
				'csh' => 'xEXT_rtehtmlarea_PlainText:behaviour',
			),
		)
	);
	$GLOBALS['TYPO3_USER_SETTINGS']['showitem'] .= ',--div--;LLL:EXT:rtehtmlarea/locallang.xml:rteSettings,rteWidth,rteHeight,rteResize,rteMaxHeight,rteCleanPasteBehaviour';
}
?>