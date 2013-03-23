<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// TYPO3 6.0 - Update localconf.php to LocalConfiguration.php
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['localConfiguration'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\LocalConfigurationUpdate';
// TYPO3 6.0 - Create page and TypoScript root template (automatically executed in 123-mode)
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['rootTemplate'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\RootTemplateUpdate';
// TYPO3 4.5 - Check the database to be utf-8 compliant
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['charsetDefaults'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\CharsetDefaultsUpdate';
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['changeCompatibilityVersion'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\CompatVersionUpdate';
// manage split includes of css_styled_contents since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['splitCscToMultipleTemplates'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\CscSplitUpdate';
// remove pagetype "not in menu" since TYPO3 4.2
// as there is an option in every pagetype
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['removeNotInMenuDoktypeConversion'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\NotInMenuUpdate';
// remove pagetype "advanced" since TYPO3 4.2
// this is merged with doctype "standard" with tab view to edit
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['mergeAdvancedDoktypeConversion'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\MergeAdvancedUpdate';
// TYPO3 6.0 - Add new tables for ExtensionManager
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['extensionManagerTables'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\ExtensionManagerTables';
// add new / outsourced system extensions since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['installSystemExtensions'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\InstallSysExtsUpdate';
// change tt_content.imagecols=0 to 1 for proper display in TCEforms since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['changeImagecolsValue'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\ImagecolsUpdate';
// register eID script for install tool AJAX calls
$TYPO3_CONF_VARS['FE']['eID_include']['tx_install_ajax'] = 'EXT:install/mod/class.tx_install_ajax.php';
// warn for t3skin installed in Version 4.4
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['checkForT3SkinInstalled'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\T3skinUpdate';
// Version 4.4: warn for set CompressionLevel and warn user to update his .htaccess
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['checkForCompressionLevel'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\CompressionLevelUpdate';
// Version 4.5: migrate workspaces to use custom stages and install the required extensions
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['migrateWorkspaces'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\MigrateWorkspacesUpdate';
// Version 4.5: Removes the ".gif" suffix from entries in sys_language
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['flagsFromSprites'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\FlagsFromSpriteUpdate';
// Version 4.5: Adds excludeable FlexForm fields to Backend group access lists (ACL)
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['addFlexformsToAcl'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\AddFlexFormsToAclUpdate';
// Version 4.5: Split tt_content image_link to newline by comma
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['imagelink'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\ImagelinkUpdate';
// Version 6.0: Migrate files content elements to use File Abstraction Layer
// Migrations of tt_content.image DB fields and captions, alt texts, etc. into sys_file_reference records.
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['sysext_file_init'] = 'TYPO3\\CMS\\Install\\Updates\\InitUpdateWizard';
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['sysext_file_images'] = 'TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard';
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['sysext_file_uploads'] = 'TYPO3\\CMS\\Install\\Updates\\TtContentUploadsUpdateWizard';
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['referenceIntegrity'] = 'TYPO3\\CMS\\Install\\Updates\\ReferenceIntegrityUpdateWizard';

$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['sysext_file_filemounts'] = 'TYPO3\\CMS\\Install\\Updates\\FilemountUpdateWizard';
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['sysext_file_rtemagicimages'] = 'TYPO3\\CMS\\Install\\Updates\\RteMagicImagesUpdateWizard';

// Version 4.7: Migrate the flexforms of MediaElement
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['mediaElementFlexform'] = 'TYPO3\\CMS\\Install\\CoreUpdates\\MediaFlexformUpdate';
?>