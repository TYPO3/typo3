<?php
if (!defined ('TYPO3_MODE'))	die ('Access denied.');


if (TYPO3_MODE == 'BE') {
	t3lib_extMgm::addModule('web','layout','top',t3lib_extMgm::extPath($_EXTKEY).'layout/');
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_layout','EXT:cms/locallang_csh_weblayout.xml');
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_info','EXT:cms/locallang_csh_webinfo.xml');

	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_cms_webinfo_page',
		t3lib_extMgm::extPath($_EXTKEY).'web_info/class.tx_cms_webinfo.php',
		'LLL:EXT:cms/locallang_tca.xml:mod_tx_cms_webinfo_page'
	);
	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_cms_webinfo_lang',
		t3lib_extMgm::extPath($_EXTKEY).'web_info/class.tx_cms_webinfo_lang.php',
		'LLL:EXT:cms/locallang_tca.xml:mod_tx_cms_webinfo_lang'
	);
}


	// Add allowed records to pages:
t3lib_extMgm::allowTableOnStandardPages('pages_language_overlay,tt_content,sys_template,sys_domain,backend_layout');


// ******************************************************************
// This is the standard TypoScript content table, tt_content
// ******************************************************************
$TCA['tt_content'] = array (
	'ctrl' => array (
		'label' => 'header',
		'label_alt' => 'subheader,bodytext',
		'sortby' => 'sorting',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'title' => 'LLL:EXT:cms/locallang_tca.xml:tt_content',
		'delete' => 'deleted',
		'versioningWS' => 2,
		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'type' => 'CType',
		'hideAtCopy' => TRUE,
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xml:LGL.prependAtCopy',
		'copyAfterDuplFields' => 'colPos,sys_language_uid',
		'useColumnsForDefaultValues' => 'colPos,sys_language_uid',
		'shadowColumnsForNewPlaceholders' => 'colPos',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'languageField' => 'sys_language_uid',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'typeicon_column' => 'CType',
		'typeicon_classes' => array(
			'header' => 'mimetypes-x-content-header',
			'textpic' => 'mimetypes-x-content-text-picture',
			'image' => 'mimetypes-x-content-image',
			'bullets' => 'mimetypes-x-content-list-bullets',
			'table' => 'mimetypes-x-content-table',
			'splash' => 'mimetypes-x-content-splash',
			'uploads' => 'mimetypes-x-content-list-files',
			'multimedia' => 'mimetypes-x-content-multimedia',
			'media' => 'mimetypes-x-content-multimedia',
			'menu' => 'mimetypes-x-content-menu',
			'list' => 'mimetypes-x-content-plugin',
			'mailform' => 'mimetypes-x-content-form',
			'search' => 'mimetypes-x-content-form-search',
			'login' => 'mimetypes-x-content-login',
			'shortcut' => 'mimetypes-x-content-link',
			'script' => 'mimetypes-x-content-script',
			'div' => 'mimetypes-x-content-divider',
			'html' => 'mimetypes-x-content-html',
			'text' => 'mimetypes-x-content-text',
			'default' => 'mimetypes-x-content-text',
		),
		'typeicons' => array (
			'header' => 'tt_content_header.gif',
			'textpic' => 'tt_content_textpic.gif',
			'image' => 'tt_content_image.gif',
			'bullets' => 'tt_content_bullets.gif',
			'table' => 'tt_content_table.gif',
			'splash' => 'tt_content_news.gif',
			'uploads' => 'tt_content_uploads.gif',
			'multimedia' => 'tt_content_mm.gif',
			'media' => 'tt_content_mm.gif',
			'menu' => 'tt_content_menu.gif',
			'list' => 'tt_content_list.gif',
			'mailform' => 'tt_content_form.gif',
			'search' => 'tt_content_search.gif',
			'login' => 'tt_content_login.gif',
			'shortcut' => 'tt_content_shortcut.gif',
			'script' => 'tt_content_script.gif',
			'div' => 'tt_content_div.gif',
			'html' => 'tt_content_html.gif'
		),
		'thumbnail' => 'image',
		'requestUpdate' => 'list_type,rte_enabled',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_tt_content.php',
		'dividers2tabs' => 1
	)
);

// ******************************************************************
// fe_users
// ******************************************************************
$TCA['fe_users'] = array (
	'ctrl' => array (
		'label' => 'username',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'fe_cruser_id' => 'fe_cruser_id',
		'title' => 'LLL:EXT:cms/locallang_tca.xml:fe_users',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'disable',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'typeicon_classes' => array(
			'default' => 'status-user-frontend',
		),
		'useColumnsForDefaultValues' => 'usergroup,lockToDomain,disable,starttime,endtime',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php',
		'dividers2tabs' => 1
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'username,password,usergroup,name,address,telephone,fax,email,title,zip,city,country,www,company',
	)
);

// ******************************************************************
// fe_groups
// ******************************************************************
$TCA['fe_groups'] = array (
	'ctrl' => array (
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xml:LGL.prependAtCopy',
		'enablecolumns' => array (
			'disabled' => 'hidden'
		),
		'title' => 'LLL:EXT:cms/locallang_tca.xml:fe_groups',
		'typeicon_classes' => array(
			'default' => 'status-user-group-frontend',
		),
		'useColumnsForDefaultValues' => 'lockToDomain',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php',
		'dividers2tabs' => 1
	)
);

// ******************************************************************
// sys_domain
// ******************************************************************
$TCA['sys_domain'] = array (
	'ctrl' => array (
		'label' => 'domainName',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'title' => 'LLL:EXT:cms/locallang_tca.xml:sys_domain',
		'iconfile' => 'domain.gif',
		'enablecolumns' => array (
			'disabled' => 'hidden'
		),
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-content-domain',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php'
	)
);

// ******************************************************************
// pages_language_overlay
// ******************************************************************
$TCA['pages_language_overlay'] = array (
	'ctrl' => array (
		'label'                           => 'title',
		'tstamp'                          => 'tstamp',
		'title'                           => 'LLL:EXT:cms/locallang_tca.xml:pages_language_overlay',
		'versioningWS'                    => TRUE,
		'versioning_followPages'          => TRUE,
		'origUid'                         => 't3_origuid',
		'crdate'                          => 'crdate',
		'cruser_id'                       => 'cruser_id',
		'delete'                          => 'deleted',
		'enablecolumns'                   => array (
			'disabled'  => 'hidden',
			'starttime' => 'starttime',
			'endtime'   => 'endtime'
		),
		'transOrigPointerField'           => 'pid',
		'transOrigPointerTable'           => 'pages',
		'transOrigDiffSourceField'        => 'l18n_diffsource',
		'shadowColumnsForNewPlaceholders' => 'title',
		'languageField'                   => 'sys_language_uid',
		'mainpalette'                     => 1,
		'dynamicConfigFile'               => t3lib_extMgm::extPath($_EXTKEY) . 'tbl_cms.php',
		'type'                            => 'doktype',
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-content-page-language-overlay',
		),

		'dividers2tabs'                   => TRUE
	)
);


// ******************************************************************
// sys_template
// ******************************************************************
$TCA['sys_template'] = array (
	'ctrl' => array (
		'label' => 'title',
		'tstamp' => 'tstamp',
		'sortby' => 'sorting',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xml:LGL.prependAtCopy',
		'title' => 'LLL:EXT:cms/locallang_tca.xml:sys_template',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'adminOnly' => 1,	// Only admin, if any
		'iconfile' => 'template.gif',
		'thumbnail' => 'resources',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'typeicon_column' => 'root',
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-content-template-extension',
			'1' => 'mimetypes-x-content-template',
		),
		'typeicons' => array (
			'0' => 'template_add.gif'
		),
		'dividers2tabs' => 1,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php'
	)
);


// ******************************************************************
// layouts
// ******************************************************************
$TCA['backend_layout'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:cms/locallang_tca.xml:backend_layout',
		'label'     => 'title',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'sortby' => 'sorting',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php',
		'iconfile' => 'backend_layout.gif',
		'selicon_field' => 'icon',
		'selicon_field_path' => 'uploads/media',
		'thumbnail' => 'resources',
	)
);

?>