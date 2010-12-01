<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect']['TYPO3.FAL.ExtDirect'] = t3lib_extMgm::extPath($_EXTKEY).'classes/dataprovider/class.tx_fal_dataprovider.php:tx_fal_dataprovider';

t3lib_extMgm::addToInsertRecords('tx_fal_sys_files');

$TCA['sys_files'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files',
		'label'     => 'file_name',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField'            => 'sys_language_uid',
		'transOrigPointerField'    => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_fal_sys_files.gif',
	),
);


t3lib_extMgm::allowTableOnStandardPages('sys_files');

$TCA['sys_files_mounts'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files_mounts',
		'label'     => 'title',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'rootLevel' => 1,
		'type' => 'storage_backend',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_fal_sys_files.gif',
	)
);

// Add columns to tt_content
$tempColumns = array (
	'image_rel' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:fal/locallang_db.xml:tt_content.image_rel',
		'config' => tx_fal_tcafunc::getFileFieldTCAConfig('image_rel', 'tt_content', 'image')
	),
	'media_rel' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:fal/locallang_db.xml:tt_content.media_rel',
		'config' => tx_fal_tcafunc::getFileFieldTCAConfig('media_rel', 'tt_content', 'media')
	),
);
t3lib_div::loadTCA('tt_content');
t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns,1);
t3lib_extMgm::addFieldsToPalette('tt_content', 'imagefiles', 'image_rel' ,'after:image');
// remove field from original TCA
#unset($GLOBALS['TCA']['tt_content']['columns']['image']);

t3lib_extMgm::addFieldsToPalette('tt_content', 'uploads', 'media_rel' ,'after:media');
// remove field from original TCA
#unset($GLOBALS['TCA']['tt_content']['columns']['media']);
// cannot remove select_key, that's annoying

// Add columns to pages
unset($tempColumns);
$tempColumns = array (
	'media_rel' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:fal/locallang_db.xml:pages.media_rel',
		'config' => tx_fal_tcafunc::getFileFieldTCAConfig('media_rel', 'pages', 'media')
	),
);
t3lib_div::loadTCA('pages');
// remove media from list, is there a smarter way to do this?
#unset($GLOBALS['TCA']['pages']['columns']['media']);

t3lib_extMgm::addTCAcolumns('pages', $tempColumns,1);
t3lib_extmgm::addFieldsToPalette('pages', 'media', 'media_rel', 'before:storage_pid');

// Add columns to pages_language_overlay
$tempColumns = array (
	'media_rel' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:fal/locallang_db.xml:pages_language_overlay.media_rel',
		'config' => tx_fal_tcafunc::getFileFieldTCAConfig('media_rel', 'pages_language_overlay', 'media')
	),
);
t3lib_div::loadTCA('pages_language_overlay');
t3lib_extMgm::addTCAcolumns('pages_language_overlay', $tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('pages_language_overlay', 'media_rel', '', 'after:media');
// remove field from original TCA
#unset($GLOBALS['TCA']['pages_language_overlay']['columns']['media']);

//@todo mattes: remove "old" fields from the backend while keeping them in the BE

	// add module after 'File'
if (!isset($TBE_MODULES['txfilelistMExtjs']))	{
	$temp_TBE_MODULES = array();
	foreach($TBE_MODULES as $key => $val) {
		if ($key === 'file') {
			$temp_TBE_MODULES[$key] = $val;
			$temp_TBE_MODULES['txfilelistMExtjs'] = $val;
		} else {
			$temp_TBE_MODULES[$key] = $val;
		}
	}

	// remove Web>File module
	unset($temp_TBE_MODULES['file']);
	$TBE_MODULES = $temp_TBE_MODULES;
	unset($temp_TBE_MODULES);
}

if(t3lib_extMgm::isLoaded('t3skin')){
	$TBE_STYLES['skinImg']['MOD:txfilelistMExtjs/list.gif'] = array(t3lib_extMgm::extRelPath('t3skin').'icons/module_file_list.gif','width="22" height="24"');
}

t3lib_extMgm::addModulePath('txfilelistMExtjs', t3lib_extMgm::extPath($_EXTKEY) . 'mod_extjs/');
t3lib_extMgm::addModule('txfilelistMExtjs', '', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod_extjs/');


?>