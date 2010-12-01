<?php
//@todo something tells me this is not right (although this is how doc_core_api says)
require_once(t3lib_extMgm::extPath($_EXTKEY) . 'classes/tceforms_wizard.php');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_fal_MigrationTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:MigrationTask.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:MigrationTask.description',
	'additionalFields' => 'tx_fal_MigrationTask_AdditionalFieldProvider'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fal']['tablesAndFieldsToMigrate'][$_EXTKEY] = array(
	'pages' => array('media'),
	'tt_content' => array('image', 'media'),
	'pages_language_overlay' => array('media')
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fal/classes/controller/class.tx_fal_migrationcontroller.php']['copyFileToPath'][$_EXTKEY] =
	'EXT:fal/classes/hooks/class.tx_fal_examplehook.php:tx_fal_ExampleHook';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][$_EXTKEY] =
	'EXT:fal/classes/hooks/class.tx_fal_hooks_extfilefunchook.php:tx_fal_hooks_ExtFileFuncHook';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess'][] =
	'EXT:fal/classes/hooks/class.tslib_fe_rootlinehook.php:tx_fal_tslibfe_rootlinehook->modifyRootline';

/**
 * Hook for t3lib_TCEforms::dbFileIcons
 * Modify selector box form-field for the db/file/select elements for own element browser
 */
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['dbFileIcons'][$_EXTKEY] =
	'EXT:fal/classes/hooks/class.tx_fal_hooks_tceforms_dbfileicons.php:tx_fal_hooks_TCEforms_dbFileIcons';

/**
 * Hook for SC_browse_links::main
 * Add our own element browser for type "sys_files"
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering'][$_EXTKEY] =
	'EXT:fal/classes/hooks/class.tx_fal_hooks_browselinks_browserrendering.php:tx_fal_hooks_browseLinks_browserRendering';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content_content.php']['modifyDBRow'][] =
	'EXT:fal/classes/hooks/class.cobjdata_hook.php:tx_fal_cobjdata_hook';

$TYPO3_CONF_VARS['SC_OPTIONS']['ExtDirect']['TYPO3.FILELIST.ExtDirect'] = 'EXT:fal/classes/dataprovider/class.tx_fal_list_dataprovider.php:tx_fal_list_DataProvider';
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = 'EXT:fal/classes/hooks/class.tx_fal_backendhook.php:tx_fal_backendhook->constructPostProcess';
tx_fal_list_Registry::registerExtDirectNamespace('TYPO3.FILELIST');
tx_fal_list_Registry::registerEbJsComponent('EXT:fal/res/js/SelectedFilesView/Bootstrap.js');
tx_fal_list_Registry::registerEbJsComponent('EXT:fal/res/js/SelectedFilesView/SelectedFilesView.js');
tx_fal_list_Registry::registerJsComponent('EXT:fal/res/js/DetailView/Bootstrap.js');
tx_fal_list_Registry::registerJsComponent('EXT:fal/res/js/DetailView/DetailView.js');
tx_fal_list_Registry::registerJsComponent('EXT:fal/res/js/FolderTree/Bootstrap.js');
tx_fal_list_Registry::registerJsComponent('EXT:fal/res/js/FolderTree/FolderTree.js');
tx_fal_list_Registry::registerJsComponent('EXT:fal/res/js/FileList/Bootstrap.js');
tx_fal_list_Registry::registerJsComponent('EXT:fal/res/js/FileList/FileList.js');
tx_fal_list_Registry::registerModJsComponent('EXT:fal/res/js/Ui/ModBootstrap.js');
tx_fal_list_Registry::registerEbJsComponent('EXT:fal/res/js/Ui/EbBootstrap.js');
tx_fal_list_Registry::registerJsComponent('EXT:fal/res/js/Ui/Ui.js');
tx_fal_list_Registry::registerCssComponent('EXT:fal/res/css/fallist.css');

$TYPO3_CONF_VARS['BE']['AJAX']['PLUPLOAD::process'] = 'EXT:fal/classes/dataprovider/class.tx_fal_plupload_dataprovider.php:tx_fal_plupload_dataprovider->processAjaxRequest';


// Remove fields from BE
t3lib_extMgm::addPageTSConfig(
	'TCEFORM {
		tt_content.image.disabled = 1
		tt_content.media.disabled = 1
		pages.media.disabled = 1
		pages_language_overlay.disabled = 1
	}'
);
?>
