<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['felogin']);

t3lib_div::loadTCA('tt_content');

if(t3lib_div::int_from_ver(TYPO3_version) >= 4002000)
	t3lib_extMgm::addPiFlexFormValue('*','FILE:EXT:'.$_EXTKEY.'/flexform.xml','login');
else
	t3lib_extMgm::addPiFlexFormValue('default','FILE:EXT:'.$_EXTKEY.'/flexform.xml');



	#replace login
$TCA['tt_content']['types']['login']['showitem']='CType;;4;button;1-1-1, header;;3;;2-2-2,pi_flexform';	

	// Adds the redirect-field to the fe_group- and fe_users-table
$tempColumns = Array (
	"felogin_redirectPid" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:felogin/locallang_db.xml:felogin_redirectPid",		
		"config" => Array (
			"type" => "group",	
			"internal_type" => "db",	
			"allowed" => "pages",	
			"size" => 1,	
			"minitems" => 0,
			"maxitems" => 1,
		)
	),
);

t3lib_div::loadTCA("fe_groups");
t3lib_extMgm::addTCAcolumns("fe_groups",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("fe_groups","felogin_redirectPid;;;;1-1-1");

t3lib_div::loadTCA("fe_users");
t3lib_extMgm::addTCAcolumns("fe_users",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("fe_users","felogin_redirectPid;;;;1-1-1");

?>
