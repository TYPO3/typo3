<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// TYPO3 6.0 - Create page and TypoScript root template (automatically executed in 123-mode)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['rootTemplate'] = 'TYPO3\\CMS\\Install\\Updates\\RootTemplateUpdate';
// TYPO3 4.5 - Check the database to be utf-8 compliant
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['charsetDefaults'] = 'TYPO3\\CMS\\Install\\Updates\\CharsetDefaultsUpdate';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['changeCompatibilityVersion'] = 'TYPO3\\CMS\\Install\\Updates\\CompatVersionUpdate';
// manage split includes of css_styled_contents since TYPO3 4.3
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['splitCscToMultipleTemplates'] = 'TYPO3\\CMS\\Install\\Updates\\CscSplitUpdate';
// remove pagetype "not in menu" since TYPO3 4.2
// as there is an option in every pagetype
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['removeNotInMenuDoktypeConversion'] = 'TYPO3\\CMS\\Install\\Updates\\NotInMenuUpdate';
// remove pagetype "advanced" since TYPO3 4.2
// this is merged with doctype "standard" with tab view to edit
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['mergeAdvancedDoktypeConversion'] = 'TYPO3\\CMS\\Install\\Updates\\MergeAdvancedUpdate';
// TYPO3 6.0 - Add new tables for ExtensionManager
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['extensionManagerTables'] = 'TYPO3\\CMS\\Install\\Updates\\ExtensionManagerTables';
// add new / outsourced system extensions since TYPO3 4.3
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['installSystemExtensions'] = 'TYPO3\\CMS\\Install\\Updates\\InstallSysExtsUpdate';
// change tt_content.imagecols=0 to 1 for proper display in TCEforms since TYPO3 4.3
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['changeImagecolsValue'] = 'TYPO3\\CMS\\Install\\Updates\\ImagecolsUpdate';
// warn for t3skin installed in Version 4.4
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['checkForT3SkinInstalled'] = 'TYPO3\\CMS\\Install\\Updates\\T3skinUpdate';
// Version 4.4: warn for set CompressionLevel and warn user to update his .htaccess
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['checkForCompressionLevel'] = 'TYPO3\\CMS\\Install\\Updates\\CompressionLevelUpdate';
// Version 4.5: migrate workspaces to use custom stages and install the required extensions
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['migrateWorkspaces'] = 'TYPO3\\CMS\\Install\\Updates\\MigrateWorkspacesUpdate';
// Version 4.5: Removes the ".gif" suffix from entries in sys_language
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['flagsFromSprites'] = 'TYPO3\\CMS\\Install\\Updates\\FlagsFromSpriteUpdate';
// Version 4.5: Adds excludeable FlexForm fields to Backend group access lists (ACL)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['addFlexformsToAcl'] = 'TYPO3\\CMS\\Install\\Updates\\AddFlexFormsToAclUpdate';
// Version 4.5: Split tt_content image_link to newline by comma
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['imagelink'] = 'TYPO3\\CMS\\Install\\Updates\\ImagelinkUpdate';
// TYPO3 6.2 - Add default backend user
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['backendUserTables'] = 'TYPO3\\CMS\\Install\\Updates\\BackendUserTables';
// Version 6.0: Migrate files content elements to use File Abstraction Layer
// Migrations of tt_content.image DB fields and captions, alt texts, etc. into sys_file_reference records.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_init'] = 'TYPO3\\CMS\\Install\\Updates\\InitUpdateWizard';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_images'] = 'TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_uploads'] = 'TYPO3\\CMS\\Install\\Updates\\TtContentUploadsUpdateWizard';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['referenceIntegrity'] = 'TYPO3\\CMS\\Install\\Updates\\ReferenceIntegrityUpdateWizard';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_filemounts'] = 'TYPO3\\CMS\\Install\\Updates\\FilemountUpdateWizard';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_rtemagicimages'] = 'TYPO3\\CMS\\Install\\Updates\\RteMagicImagesUpdateWizard';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_rtefilelinks'] = 'TYPO3\\CMS\\Install\\Updates\\RteFileLinksUpdateWizard';

// Version 4.7: Migrate the flexforms of MediaElement
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['mediaElementFlexform'] = 'TYPO3\\CMS\\Install\\Updates\\MediaFlexformUpdate';
?>