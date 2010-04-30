<?php

########################################################################
# Extension Manager/Repository config file for ext "lang".
#
# Auto generated 25-11-2009 22:02
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
	'_md5_values_when_last_written' => 'a:159:{s:8:"lang.php";s:4:"7d27";s:21:"locallang_alt_doc.xml";s:4:"774e";s:23:"locallang_alt_intro.xml";s:4:"a4e1";s:26:"locallang_browse_links.xml";s:4:"9b88";s:20:"locallang_common.xml";s:4:"4034";s:18:"locallang_core.xml";s:4:"5d4d";s:27:"locallang_csh_be_groups.xml";s:4:"1f91";s:26:"locallang_csh_be_users.xml";s:4:"7bc0";s:24:"locallang_csh_corebe.xml";s:4:"a1d9";s:20:"locallang_csh_em.xml";s:4:"ee48";s:23:"locallang_csh_pages.xml";s:4:"668a";s:26:"locallang_csh_sysfilem.xml";s:4:"f42a";s:25:"locallang_csh_syslang.xml";s:4:"ae54";s:23:"locallang_csh_sysws.xml";s:4:"87c4";s:26:"locallang_csh_web_func.xml";s:4:"4e98";s:26:"locallang_csh_web_info.xml";s:4:"e3de";s:21:"locallang_general.xml";s:4:"ab26";s:19:"locallang_login.xml";s:4:"4d8a";s:18:"locallang_misc.xml";s:4:"d103";s:28:"locallang_mod_admintools.xml";s:4:"9475";s:21:"locallang_mod_doc.xml";s:4:"aaf7";s:22:"locallang_mod_file.xml";s:4:"b3d5";s:27:"locallang_mod_file_list.xml";s:4:"7d5a";s:22:"locallang_mod_help.xml";s:4:"338c";s:28:"locallang_mod_help_about.xml";s:4:"602f";s:32:"locallang_mod_help_cshmanual.xml";s:4:"ed9b";s:23:"locallang_mod_tools.xml";s:4:"cb9f";s:26:"locallang_mod_tools_em.xml";s:4:"637b";s:22:"locallang_mod_user.xml";s:4:"f3ed";s:25:"locallang_mod_user_ws.xml";s:4:"b263";s:27:"locallang_mod_usertools.xml";s:4:"c2cc";s:21:"locallang_mod_web.xml";s:4:"438e";s:26:"locallang_mod_web_func.xml";s:4:"9205";s:26:"locallang_mod_web_info.xml";s:4:"c189";s:26:"locallang_mod_web_list.xml";s:4:"f1c6";s:26:"locallang_mod_web_perm.xml";s:4:"0532";s:25:"locallang_show_rechis.xml";s:4:"8a05";s:17:"locallang_tca.xml";s:4:"09e6";s:21:"locallang_tcemain.xml";s:4:"7d44";s:18:"locallang_tsfe.xml";s:4:"7bd1";s:22:"locallang_tsparser.xml";s:4:"22da";s:23:"locallang_view_help.xml";s:4:"8274";s:21:"locallang_wizards.xml";s:4:"84bf";s:25:"cshimages/be_groups_1.png";s:4:"c495";s:26:"cshimages/be_groups_10.png";s:4:"0af0";s:26:"cshimages/be_groups_11.png";s:4:"33c1";s:26:"cshimages/be_groups_12.png";s:4:"89d6";s:26:"cshimages/be_groups_13.png";s:4:"8460";s:26:"cshimages/be_groups_14.png";s:4:"0bbd";s:26:"cshimages/be_groups_15.png";s:4:"7600";s:26:"cshimages/be_groups_16.png";s:4:"3c43";s:26:"cshimages/be_groups_17.png";s:4:"d4f3";s:26:"cshimages/be_groups_18.png";s:4:"559a";s:26:"cshimages/be_groups_19.png";s:4:"e322";s:25:"cshimages/be_groups_2.png";s:4:"879b";s:26:"cshimages/be_groups_20.png";s:4:"315d";s:25:"cshimages/be_groups_3.png";s:4:"460a";s:25:"cshimages/be_groups_4.png";s:4:"a593";s:25:"cshimages/be_groups_5.png";s:4:"6c61";s:25:"cshimages/be_groups_6.png";s:4:"55a6";s:25:"cshimages/be_groups_7.png";s:4:"0937";s:25:"cshimages/be_groups_8.png";s:4:"a736";s:25:"cshimages/be_groups_9.png";s:4:"f626";s:22:"cshimages/beuser_1.png";s:4:"9938";s:22:"cshimages/beuser_2.png";s:4:"a3e3";s:22:"cshimages/beuser_3.png";s:4:"8666";s:22:"cshimages/beuser_4.png";s:4:"c2bc";s:20:"cshimages/core_1.png";s:4:"a009";s:21:"cshimages/core_10.png";s:4:"f545";s:21:"cshimages/core_11.png";s:4:"44bb";s:21:"cshimages/core_12.png";s:4:"a9e7";s:21:"cshimages/core_13.png";s:4:"40e2";s:21:"cshimages/core_14.png";s:4:"561e";s:21:"cshimages/core_15.png";s:4:"1aac";s:21:"cshimages/core_16.png";s:4:"0e24";s:21:"cshimages/core_17.png";s:4:"0d9f";s:21:"cshimages/core_18.png";s:4:"58cf";s:21:"cshimages/core_19.png";s:4:"a57f";s:20:"cshimages/core_2.png";s:4:"0cd1";s:21:"cshimages/core_20.png";s:4:"5fb4";s:21:"cshimages/core_21.png";s:4:"9073";s:21:"cshimages/core_22.png";s:4:"fc41";s:21:"cshimages/core_23.png";s:4:"e25e";s:21:"cshimages/core_24.png";s:4:"6611";s:21:"cshimages/core_25.png";s:4:"7130";s:21:"cshimages/core_26.png";s:4:"aa98";s:21:"cshimages/core_27.png";s:4:"3332";s:21:"cshimages/core_28.png";s:4:"2db6";s:21:"cshimages/core_29.png";s:4:"3a92";s:20:"cshimages/core_3.png";s:4:"9e2e";s:21:"cshimages/core_30.png";s:4:"4e80";s:21:"cshimages/core_31.png";s:4:"dabf";s:21:"cshimages/core_32.png";s:4:"96c9";s:21:"cshimages/core_33.png";s:4:"b3e3";s:21:"cshimages/core_34.png";s:4:"74e9";s:21:"cshimages/core_35.png";s:4:"85ab";s:21:"cshimages/core_36.png";s:4:"b45a";s:21:"cshimages/core_37.png";s:4:"1b25";s:21:"cshimages/core_38.png";s:4:"d748";s:21:"cshimages/core_39.png";s:4:"3b65";s:20:"cshimages/core_4.png";s:4:"f17f";s:21:"cshimages/core_40.png";s:4:"b962";s:21:"cshimages/core_41.png";s:4:"9c2d";s:21:"cshimages/core_42.png";s:4:"1ea9";s:21:"cshimages/core_43.png";s:4:"ace6";s:21:"cshimages/core_44.png";s:4:"c93d";s:21:"cshimages/core_46.png";s:4:"18a9";s:21:"cshimages/core_47.png";s:4:"e14f";s:21:"cshimages/core_48.png";s:4:"07c4";s:21:"cshimages/core_49.png";s:4:"0f1b";s:20:"cshimages/core_5.png";s:4:"9d84";s:21:"cshimages/core_50.png";s:4:"10f4";s:21:"cshimages/core_51.png";s:4:"2df7";s:21:"cshimages/core_52.png";s:4:"a7c4";s:21:"cshimages/core_53.png";s:4:"9c3b";s:21:"cshimages/core_54.png";s:4:"16b7";s:21:"cshimages/core_55.png";s:4:"89d3";s:21:"cshimages/core_56.png";s:4:"f442";s:21:"cshimages/core_57.png";s:4:"4a31";s:21:"cshimages/core_58.png";s:4:"a175";s:21:"cshimages/core_59.png";s:4:"2952";s:20:"cshimages/core_6.png";s:4:"c4cb";s:21:"cshimages/core_60.png";s:4:"ef52";s:21:"cshimages/core_61.png";s:4:"0199";s:21:"cshimages/core_62.png";s:4:"3bd2";s:21:"cshimages/core_63.png";s:4:"c9f4";s:21:"cshimages/core_64.png";s:4:"d904";s:21:"cshimages/core_65.png";s:4:"f42f";s:21:"cshimages/core_67.png";s:4:"9fdc";s:21:"cshimages/core_68.png";s:4:"59f5";s:21:"cshimages/core_69.png";s:4:"27b0";s:20:"cshimages/core_7.png";s:4:"fb4c";s:21:"cshimages/core_70.png";s:4:"cf98";s:20:"cshimages/core_8.png";s:4:"07ae";s:20:"cshimages/core_9.png";s:4:"fa63";s:18:"cshimages/em_1.png";s:4:"8544";s:19:"cshimages/em_10.png";s:4:"4887";s:19:"cshimages/em_11.png";s:4:"af9a";s:19:"cshimages/em_12.png";s:4:"054b";s:18:"cshimages/em_2.png";s:4:"3cca";s:18:"cshimages/em_3.png";s:4:"2b8b";s:18:"cshimages/em_4.png";s:4:"ca96";s:18:"cshimages/em_5.png";s:4:"3443";s:18:"cshimages/em_6.png";s:4:"a92c";s:18:"cshimages/em_7.png";s:4:"febd";s:18:"cshimages/em_8.png";s:4:"f42c";s:18:"cshimages/em_9.png";s:4:"cc2f";s:25:"cshimages/filemount_1.png";s:4:"7180";s:19:"cshimages/login.png";s:4:"b888";s:21:"cshimages/pages_1.png";s:4:"c94b";s:21:"cshimages/pages_2.png";s:4:"2961";s:21:"cshimages/pages_3.png";s:4:"efc6";s:21:"cshimages/pages_4.png";s:4:"a95d";s:21:"cshimages/pages_5.png";s:4:"fefe";s:21:"cshimages/pages_6.png";s:4:"5c08";s:21:"cshimages/pages_7.png";s:4:"fce9";s:21:"cshimages/pages_8.png";s:4:"6a77";s:34:"cshimages/pagetree_overview_10.png";s:4:"047d";s:34:"cshimages/pagetree_overview_11.png";s:4:"0c04";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.3.0-0.0.0',
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
