<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['felogin']);

t3lib_div::loadTCA('tt_content');

if(t3lib_div::int_from_ver(TYPO3_version) >= 4002000)
	t3lib_extMgm::addPiFlexFormValue('*','FILE:EXT:'.$_EXTKEY.'/flexform.xml','login');
else
	t3lib_extMgm::addPiFlexFormValue('default','FILE:EXT:'.$_EXTKEY.'/flexform.xml');

t3lib_extMgm::addTcaSelectItem(
	'tt_content',
	'CType',
	array(
		'LLL:EXT:cms/locallang_ttc.xml:CType.I.10',
		'login',
		'i/tt_content_login.gif',
	),
	'mailform',
	'after'
);

$TCA['tt_content']['types']['login']['showitem'] = '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
													--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
													--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.plugin,
													pi_flexform;;;;1-1-1,
													--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
													--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
													--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
													--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
													--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
													--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.behaviour,
													--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended';

	// Adds the redirect field to the fe_groups table
$tempColumns = array(
	'felogin_redirectPid' => array(
		'exclude' => 1,
		'label'  => 'LLL:EXT:felogin/locallang_db.xml:felogin_redirectPid',
		'config' => array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'pages',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
);

t3lib_div::loadTCA('fe_groups');
t3lib_extMgm::addTCAcolumns('fe_groups', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('fe_groups', 'felogin_redirectPid;;;;1-1-1');

	// Adds the redirect field and the forgotHash field to the fe_users-table
$tempColumns = array (
	"felogin_redirectPid" => array (
		"exclude" => 1,
		"label" => "LLL:EXT:felogin/locallang_db.xml:felogin_redirectPid",
		"config" => array (
			"type" => "group",
			"internal_type" => "db",
			"allowed" => "pages",
			"size" => 1,
			"minitems" => 0,
			"maxitems" => 1,
		)
	),
	'felogin_forgotHash' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:felogin/locallang_db.xml:felogin_forgotHash',
		'config' => array (
			'type' => 'passthrough',
		)
	),
);

t3lib_div::loadTCA("fe_users");
t3lib_extMgm::addTCAcolumns("fe_users",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("fe_users","felogin_redirectPid;;;;1-1-1");

?>