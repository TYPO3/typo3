<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['changeCompatibilityVersion'] = 'tx_coreupdates_compatversion';

	// manage split includes of css_styled_contents since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['splitCscToMultipleTemplates'] = 'tx_coreupdates_cscsplit';

	// remove pagetype "not in menu" since TYPO3 4.2
	// as there is an option in every pagetype
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['removeNotInMenuDoktypeConversion'] = 'tx_coreupdates_notinmenu';

	// remove pagetype "advanced" since TYPO3 4.2
	// this is merged with doctype "standard" with tab view to edit
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['mergeAdvancedDoktypeConversion'] = 'tx_coreupdates_mergeadvanced';

	// add outsourced system extensions since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['installSystemExtensions'] = 'tx_coreupdates_installsysexts';

	// new system extensions since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['installNewSystemExtensions'] = 'tx_coreupdates_installnewsysexts';

	// change tt_content.imagecols=0 to 1 for proper display in TCEforms since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['changeImagecolsValue'] = 'tx_coreupdates_imagecols';

	// register eID script for install tool AJAX calls
$TYPO3_CONF_VARS['FE']['eID_include']['tx_install_ajax'] = 'EXT:install/mod/class.tx_install_ajax.php';

	// install versioning since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['installVersioning'] = 'tx_coreupdates_installversioning';

	// add static_template if needed (since TYPO3 4.4 this table is not standard)
	// if needed, sysext statictables is loaded, which gives back functionality
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['checkForStaticTypoScriptTemplates'] = 'tx_coreupdates_statictemplates';

	// warn for t3skin installed in Version 4.4
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['checkForT3SkinInstalled'] = 'tx_coreupdates_t3skin';

	// Version 4.4: warn for set CompressionLevel and warn user to update his .htaccess
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['checkForCompressionLevel'] = 'tx_coreupdates_compressionlevel';

?>
