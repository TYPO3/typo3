<?php

########################################################################
# Extension Manager/Repository config file for ext "lang".
#
# Auto generated 26-01-2011 20:08
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'System language labels',
	'description' => 'Contains all the core language labels in a set of files mostly of the "locallang" format. This extension is always required in a TYPO3 install.',
	'category' => 'be',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => 'top',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => 1,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => 'S',
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'Curby Soft Multimedia',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.3.0',
	'_md5_values_when_last_written' => 'a:163:{s:8:"lang.php";s:4:"e281";s:21:"locallang_alt_doc.xml";s:4:"774e";s:23:"locallang_alt_intro.xml";s:4:"a4e1";s:26:"locallang_browse_links.xml";s:4:"8449";s:20:"locallang_common.xml";s:4:"fc43";s:18:"locallang_core.xml";s:4:"cbe9";s:27:"locallang_csh_be_groups.xml";s:4:"5b0b";s:26:"locallang_csh_be_users.xml";s:4:"7bc0";s:24:"locallang_csh_corebe.xml";s:4:"db84";s:20:"locallang_csh_em.xml";s:4:"ee48";s:23:"locallang_csh_pages.xml";s:4:"668a";s:26:"locallang_csh_sysfilem.xml";s:4:"f42a";s:25:"locallang_csh_syslang.xml";s:4:"ae54";s:25:"locallang_csh_sysnews.xml";s:4:"6050";s:23:"locallang_csh_sysws.xml";s:4:"87c4";s:26:"locallang_csh_web_func.xml";s:4:"4e98";s:26:"locallang_csh_web_info.xml";s:4:"e3de";s:21:"locallang_general.xml";s:4:"4eb6";s:19:"locallang_login.xml";s:4:"4d8a";s:18:"locallang_misc.xml";s:4:"4546";s:28:"locallang_mod_admintools.xml";s:4:"9475";s:21:"locallang_mod_doc.xml";s:4:"aaf7";s:22:"locallang_mod_file.xml";s:4:"b3d5";s:27:"locallang_mod_file_list.xml";s:4:"7d5a";s:22:"locallang_mod_help.xml";s:4:"338c";s:28:"locallang_mod_help_about.xml";s:4:"602f";s:32:"locallang_mod_help_cshmanual.xml";s:4:"ed9b";s:23:"locallang_mod_tools.xml";s:4:"cb9f";s:26:"locallang_mod_tools_em.xml";s:4:"1e8e";s:22:"locallang_mod_user.xml";s:4:"f3ed";s:25:"locallang_mod_user_ws.xml";s:4:"418c";s:27:"locallang_mod_usertools.xml";s:4:"c2cc";s:21:"locallang_mod_web.xml";s:4:"438e";s:26:"locallang_mod_web_func.xml";s:4:"9205";s:26:"locallang_mod_web_info.xml";s:4:"c189";s:26:"locallang_mod_web_list.xml";s:4:"f1c6";s:26:"locallang_mod_web_perm.xml";s:4:"0532";s:25:"locallang_show_rechis.xml";s:4:"8a05";s:30:"locallang_t3lib_fullsearch.xml";s:4:"497d";s:17:"locallang_tca.xml";s:4:"ff23";s:21:"locallang_tcemain.xml";s:4:"7d44";s:18:"locallang_tsfe.xml";s:4:"be24";s:22:"locallang_tsparser.xml";s:4:"22da";s:23:"locallang_view_help.xml";s:4:"8274";s:21:"locallang_wizards.xml";s:4:"c244";s:28:"4.5/locallang_csh_corebe.xml";s:4:"d905";s:27:"4.5/locallang_csh_pages.xml";s:4:"2094";s:25:"cshimages/be_groups_1.png";s:4:"5f73";s:26:"cshimages/be_groups_10.png";s:4:"8794";s:26:"cshimages/be_groups_11.png";s:4:"bd16";s:26:"cshimages/be_groups_12.png";s:4:"a5d2";s:26:"cshimages/be_groups_13.png";s:4:"9a54";s:26:"cshimages/be_groups_14.png";s:4:"d28f";s:26:"cshimages/be_groups_15.png";s:4:"3f43";s:26:"cshimages/be_groups_16.png";s:4:"dae1";s:26:"cshimages/be_groups_17.png";s:4:"33ef";s:26:"cshimages/be_groups_18.png";s:4:"1aa6";s:26:"cshimages/be_groups_19.png";s:4:"c852";s:25:"cshimages/be_groups_2.png";s:4:"c07c";s:26:"cshimages/be_groups_20.png";s:4:"4829";s:25:"cshimages/be_groups_3.png";s:4:"a434";s:25:"cshimages/be_groups_4.png";s:4:"282e";s:25:"cshimages/be_groups_5.png";s:4:"5303";s:25:"cshimages/be_groups_6.png";s:4:"30ba";s:25:"cshimages/be_groups_7.png";s:4:"9826";s:25:"cshimages/be_groups_8.png";s:4:"0440";s:25:"cshimages/be_groups_9.png";s:4:"ac12";s:22:"cshimages/beuser_1.png";s:4:"6605";s:22:"cshimages/beuser_2.png";s:4:"5fa7";s:22:"cshimages/beuser_3.png";s:4:"8d27";s:22:"cshimages/beuser_4.png";s:4:"2f0c";s:20:"cshimages/core_1.png";s:4:"f02d";s:21:"cshimages/core_10.png";s:4:"945b";s:21:"cshimages/core_11.png";s:4:"0db5";s:21:"cshimages/core_12.png";s:4:"2130";s:21:"cshimages/core_13.png";s:4:"56f3";s:21:"cshimages/core_14.png";s:4:"7ce0";s:21:"cshimages/core_15.png";s:4:"4768";s:21:"cshimages/core_16.png";s:4:"9877";s:21:"cshimages/core_17.png";s:4:"0f58";s:21:"cshimages/core_18.png";s:4:"2866";s:21:"cshimages/core_19.png";s:4:"3200";s:20:"cshimages/core_2.png";s:4:"406e";s:21:"cshimages/core_20.png";s:4:"5328";s:21:"cshimages/core_21.png";s:4:"129b";s:21:"cshimages/core_22.png";s:4:"e3d6";s:21:"cshimages/core_23.png";s:4:"2a82";s:21:"cshimages/core_24.png";s:4:"beba";s:21:"cshimages/core_25.png";s:4:"879e";s:21:"cshimages/core_26.png";s:4:"adb4";s:21:"cshimages/core_27.png";s:4:"bba6";s:21:"cshimages/core_28.png";s:4:"389e";s:21:"cshimages/core_29.png";s:4:"a99a";s:20:"cshimages/core_3.png";s:4:"051b";s:21:"cshimages/core_30.png";s:4:"f2a7";s:21:"cshimages/core_31.png";s:4:"f130";s:21:"cshimages/core_32.png";s:4:"60f0";s:21:"cshimages/core_33.png";s:4:"015e";s:21:"cshimages/core_34.png";s:4:"260a";s:21:"cshimages/core_35.png";s:4:"c27c";s:21:"cshimages/core_36.png";s:4:"adac";s:21:"cshimages/core_37.png";s:4:"4cc0";s:21:"cshimages/core_38.png";s:4:"3bc6";s:21:"cshimages/core_39.png";s:4:"7e9f";s:20:"cshimages/core_4.png";s:4:"4324";s:21:"cshimages/core_40.png";s:4:"6559";s:21:"cshimages/core_41.png";s:4:"37bd";s:21:"cshimages/core_42.png";s:4:"dc47";s:21:"cshimages/core_43.png";s:4:"2e61";s:21:"cshimages/core_44.png";s:4:"beb2";s:21:"cshimages/core_46.png";s:4:"ad1e";s:21:"cshimages/core_47.png";s:4:"0cf7";s:21:"cshimages/core_48.png";s:4:"8b05";s:21:"cshimages/core_49.png";s:4:"4aa8";s:20:"cshimages/core_5.png";s:4:"c024";s:21:"cshimages/core_50.png";s:4:"0f9d";s:21:"cshimages/core_51.png";s:4:"3933";s:21:"cshimages/core_52.png";s:4:"82f2";s:21:"cshimages/core_53.png";s:4:"6201";s:21:"cshimages/core_54.png";s:4:"1253";s:21:"cshimages/core_55.png";s:4:"9028";s:21:"cshimages/core_56.png";s:4:"d399";s:21:"cshimages/core_57.png";s:4:"0c75";s:21:"cshimages/core_58.png";s:4:"90a4";s:21:"cshimages/core_59.png";s:4:"d1ea";s:20:"cshimages/core_6.png";s:4:"afa6";s:21:"cshimages/core_60.png";s:4:"deff";s:21:"cshimages/core_61.png";s:4:"b47e";s:21:"cshimages/core_62.png";s:4:"8027";s:21:"cshimages/core_63.png";s:4:"8dd7";s:21:"cshimages/core_64.png";s:4:"497d";s:21:"cshimages/core_65.png";s:4:"748b";s:21:"cshimages/core_67.png";s:4:"f74b";s:21:"cshimages/core_68.png";s:4:"b706";s:21:"cshimages/core_69.png";s:4:"19d7";s:20:"cshimages/core_7.png";s:4:"621b";s:21:"cshimages/core_70.png";s:4:"48ee";s:20:"cshimages/core_8.png";s:4:"f151";s:20:"cshimages/core_9.png";s:4:"a53f";s:18:"cshimages/em_1.png";s:4:"2a6e";s:19:"cshimages/em_10.png";s:4:"428d";s:19:"cshimages/em_11.png";s:4:"cac6";s:19:"cshimages/em_12.png";s:4:"7a95";s:18:"cshimages/em_2.png";s:4:"0d31";s:18:"cshimages/em_3.png";s:4:"7511";s:18:"cshimages/em_4.png";s:4:"755a";s:18:"cshimages/em_5.png";s:4:"cb16";s:18:"cshimages/em_6.png";s:4:"b5a2";s:18:"cshimages/em_7.png";s:4:"9ac7";s:18:"cshimages/em_8.png";s:4:"572d";s:18:"cshimages/em_9.png";s:4:"50d5";s:25:"cshimages/filemount_1.png";s:4:"30ba";s:19:"cshimages/login.png";s:4:"e60f";s:21:"cshimages/pages_1.png";s:4:"8507";s:21:"cshimages/pages_2.png";s:4:"0eed";s:21:"cshimages/pages_3.png";s:4:"0abc";s:21:"cshimages/pages_4.png";s:4:"d0c5";s:21:"cshimages/pages_5.png";s:4:"0e2e";s:21:"cshimages/pages_6.png";s:4:"e15a";s:21:"cshimages/pages_7.png";s:4:"2a8b";s:21:"cshimages/pages_8.png";s:4:"ec93";s:34:"cshimages/pagetree_overview_10.png";s:4:"10c0";s:34:"cshimages/pagetree_overview_11.png";s:4:"d3e3";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.4.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
);

?>