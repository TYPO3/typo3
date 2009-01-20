<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['changeCompatibilityVersion'] = 'tx_coreupdates_compatversion';

	// remove pagetype "not in menu" since TYPO3 4.2
	// as there is an option in every pagetype
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['removeNotInMenuDoktypeConversion'] = 'tx_coreupdates_notinmenu';

	// remove pagetype "advanced" since TYPO3 4.2
	// this is merged with doctype "standard" with tab view to edit
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['mergeAdvancedDoktypeConversion'] = 'tx_coreupdates_mergeadvanced';

	// add outsourced system extensions since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['installSystemExtensions'] = 'tx_coreupdates_installsysexts';

	// change tt_content.imagecols=0 to 1 for proper display in TCEforms since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['changeImagecolsValue'] = 'tx_coreupdates_imagecols';

	// register eID script for ecryption key AJAX call
$TYPO3_CONF_VARS['FE']['eID_include']['tx_install_eid'] = 'EXT:install/mod/class.tx_install_eid.php';
?>
