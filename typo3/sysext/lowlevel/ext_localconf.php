<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	// Setting up scripts that can be run from the cli_dispatch.phpsh script.
	$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys']['lowlevel_refindex'] = array('EXT:lowlevel/dbint/cli/refindex_cli.php', '_CLI_lowlevel');
	$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys']['lowlevel_cleaner'] = array('EXT:lowlevel/dbint/cli/cleaner_cli.php', '_CLI_lowlevel');
	$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys']['lowlevel_admin'] = array('EXT:lowlevel/admin_cli.php', '_CLI_lowlevel');
	$TYPO3_CONF_VARS['EXTCONF']['lowlevel']['cleanerModules']['missing_files'] = array('EXT:lowlevel/clmods/class.missing_files.php:TYPO3\\CMS\\Lowlevel\\MissingFilesCommand');
	$TYPO3_CONF_VARS['EXTCONF']['lowlevel']['cleanerModules']['missing_relations'] = array('EXT:lowlevel/clmods/class.missing_relations.php:TYPO3\\CMS\\Lowlevel\\MissingRelationsCommand');
	$TYPO3_CONF_VARS['EXTCONF']['lowlevel']['cleanerModules']['double_files'] = array('EXT:lowlevel/clmods/class.double_files.php:TYPO3\\CMS\\Lowlevel\\DoubleFilesCommand');
	$TYPO3_CONF_VARS['EXTCONF']['lowlevel']['cleanerModules']['rte_images'] = array('EXT:lowlevel/clmods/class.rte_images.php:TYPO3\\CMS\\Lowlevel\\RteImagesCommand');
	$TYPO3_CONF_VARS['EXTCONF']['lowlevel']['cleanerModules']['lost_files'] = array('EXT:lowlevel/clmods/class.lost_files.php:TYPO3\\CMS\\Lowlevel\\LostFilesCommand');
	$TYPO3_CONF_VARS['EXTCONF']['lowlevel']['cleanerModules']['orphan_records'] = array('EXT:lowlevel/clmods/class.orphan_records.php:TYPO3\\CMS\\Lowlevel\\OrphanRecordsCommand');
	$TYPO3_CONF_VARS['EXTCONF']['lowlevel']['cleanerModules']['deleted'] = array('EXT:lowlevel/clmods/class.deleted.php:TYPO3\\CMS\\Lowlevel\\DeletedRecordsCommand');
	$TYPO3_CONF_VARS['EXTCONF']['lowlevel']['cleanerModules']['versions'] = array('EXT:lowlevel/clmods/class.versions.php:TYPO3\\CMS\\Lowlevel\\VersionsCommand');
	$TYPO3_CONF_VARS['EXTCONF']['lowlevel']['cleanerModules']['cleanflexform'] = array('EXT:lowlevel/clmods/class.cleanflexform.php:TYPO3\\CMS\\Lowlevel\\CleanFlexformCommand');
	$TYPO3_CONF_VARS['EXTCONF']['lowlevel']['cleanerModules']['syslog'] = array('EXT:lowlevel/clmods/class.syslog.php:TYPO3\\CMS\\Lowlevel\\SyslogCommand');
}
?>