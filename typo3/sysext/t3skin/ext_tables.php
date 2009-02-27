<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{

	$presetSkinImgs = is_array($TBE_STYLES['skinImg']) ? $TBE_STYLES['skinImg'] : array();	// Means, support for other extensions to add own icons...

	/**
	 * Setting up backend styles and colors
	 */
	$TBE_STYLES['mainColors'] = Array (	// Always use #xxxxxx color definitions!
	'bgColor' => '#FFFFFF',			// Light background color
	'bgColor2' => '#FEFEFE',		// Steel-blue
	'bgColor3' => '#F1F3F5',		// dok.color
	'bgColor4' => '#E6E9EB',		// light tablerow background, brownish
	'bgColor5' => '#F8F9FB',		// light tablerow background, greenish
	'bgColor6' => '#E6E9EB',		// light tablerow background, yellowish, for section headers. Light.
	'hoverColor' => '#FF0000',
	'navFrameHL' => '#F8F9FB'
	);

	$TBE_STYLES['colorschemes'][0]='-|class-main1,-|class-main2,-|class-main3,-|class-main4,-|class-main5';
	$TBE_STYLES['colorschemes'][1]='-|class-main11,-|class-main12,-|class-main13,-|class-main14,-|class-main15';
	$TBE_STYLES['colorschemes'][2]='-|class-main21,-|class-main22,-|class-main23,-|class-main24,-|class-main25';
	$TBE_STYLES['colorschemes'][3]='-|class-main31,-|class-main32,-|class-main33,-|class-main34,-|class-main35';
	$TBE_STYLES['colorschemes'][4]='-|class-main41,-|class-main42,-|class-main43,-|class-main44,-|class-main45';
	$TBE_STYLES['colorschemes'][5]='-|class-main51,-|class-main52,-|class-main53,-|class-main54,-|class-main55';

	$TBE_STYLES['styleschemes'][0]['all'] = 'CLASS: formField';
	$TBE_STYLES['styleschemes'][1]['all'] = 'CLASS: formField1';
	$TBE_STYLES['styleschemes'][2]['all'] = 'CLASS: formField2';
	$TBE_STYLES['styleschemes'][3]['all'] = 'CLASS: formField3';
	$TBE_STYLES['styleschemes'][4]['all'] = 'CLASS: formField4';
	$TBE_STYLES['styleschemes'][5]['all'] = 'CLASS: formField5';

	$TBE_STYLES['styleschemes'][0]['check'] = 'CLASS: checkbox';
	$TBE_STYLES['styleschemes'][1]['check'] = 'CLASS: checkbox';
	$TBE_STYLES['styleschemes'][2]['check'] = 'CLASS: checkbox';
	$TBE_STYLES['styleschemes'][3]['check'] = 'CLASS: checkbox';
	$TBE_STYLES['styleschemes'][4]['check'] = 'CLASS: checkbox';
	$TBE_STYLES['styleschemes'][5]['check'] = 'CLASS: checkbox';

	$TBE_STYLES['styleschemes'][0]['radio'] = 'CLASS: radio';
	$TBE_STYLES['styleschemes'][1]['radio'] = 'CLASS: radio';
	$TBE_STYLES['styleschemes'][2]['radio'] = 'CLASS: radio';
	$TBE_STYLES['styleschemes'][3]['radio'] = 'CLASS: radio';
	$TBE_STYLES['styleschemes'][4]['radio'] = 'CLASS: radio';
	$TBE_STYLES['styleschemes'][5]['radio'] = 'CLASS: radio';

	$TBE_STYLES['styleschemes'][0]['select'] = 'CLASS: select';
	$TBE_STYLES['styleschemes'][1]['select'] = 'CLASS: select';
	$TBE_STYLES['styleschemes'][2]['select'] = 'CLASS: select';
	$TBE_STYLES['styleschemes'][3]['select'] = 'CLASS: select';
	$TBE_STYLES['styleschemes'][4]['select'] = 'CLASS: select';
	$TBE_STYLES['styleschemes'][5]['select'] = 'CLASS: select';

	$TBE_STYLES['borderschemes'][0]= array('','','','wrapperTable');
	$TBE_STYLES['borderschemes'][1]= array('','','','wrapperTable1');
	$TBE_STYLES['borderschemes'][2]= array('','','','wrapperTable2');
	$TBE_STYLES['borderschemes'][3]= array('','','','wrapperTable3');
	$TBE_STYLES['borderschemes'][4]= array('','','','wrapperTable4');
	$TBE_STYLES['borderschemes'][5]= array('','','','wrapperTable5');



	// Setting the relative path to the extension in temp. variable:
	$temp_eP = t3lib_extMgm::extRelPath($_EXTKEY);

	// Setting login box image rotation folder:
	$TBE_STYLES['loginBoxImage_rotationFolder'] = $temp_eP.'images/login/';
	$TBE_STYLES['loginBoxImage_author']['loginimage_4_1.jpg'] = 'Photo by Ture Andersen (www.tureandersen.dk)';
	#$TBE_STYLES['loginBoxImage_rotationFolder'] = '';

	// Setting up stylesheets (See template() constructor!)
	#	$TBE_STYLES['stylesheet'] = $temp_eP.'stylesheets/stylesheet.css';				// Alternative stylesheet to the default "typo3/stylesheet.css" stylesheet.
	#	$TBE_STYLES['stylesheet2'] = $temp_eP.'stylesheets/stylesheet.css';										// Additional stylesheet (not used by default).  Set BEFORE any in-document styles
	$TBE_STYLES['styleSheetFile_post'] = $temp_eP.'stylesheets/stylesheet_post.css';								// Additional stylesheet. Set AFTER any in-document styles
	#	$TBE_STYLES['inDocStyles_TBEstyle'] = '* {text-align: right;}';										// Additional default in-document styles.

	// Alternative dimensions for frameset sizes:
	$TBE_STYLES['dims']['leftMenuFrameW']=140;		// Left menu frame width
	$TBE_STYLES['dims']['topFrameH']=45;			// Top frame heigth
	$TBE_STYLES['dims']['shortcutFrameH']=35;		// Shortcut frame height
	$TBE_STYLES['dims']['selMenuFrame']=200;		// Width of the selector box menu frame
	$TBE_STYLES['dims']['navFrameWidth']=260;		// Default navigation frame width

	$TBE_STYLES['border'] = $temp_eP.'noborder.html';

	// Setting roll-over background color for click menus:
	// Notice, this line uses the the 'scriptIDindex' feature to override another value in this array (namely $TBE_STYLES['mainColors']['bgColor5']), for a specific script "typo3/alt_clickmenu.php"
	$TBE_STYLES['scriptIDindex']['typo3/alt_clickmenu.php']['mainColors']['bgColor5']='#F8F9FB';

	// Setting up auto detection of alternative icons:
	$TBE_STYLES['skinImgAutoCfg']=array(
	'absDir' => t3lib_extMgm::extPath($_EXTKEY).'icons/',
	'relDir' => t3lib_extMgm::extRelPath($_EXTKEY).'icons/',
	'forceFileExtension' => 'gif',	// Force to look for PNG alternatives...
	#		'scaleFactor' => 2/3,	// Scaling factor, default is 1
	);

	// Manual setting up of alternative icons. This is mainly for module icons which has a special prefix:
	$TBE_STYLES['skinImg'] = array_merge($presetSkinImgs, array (
	'gfx/ol/blank.gif' => array('clear.gif','width="14" height="14"'),
	'MOD:web/website.gif'  => array($temp_eP.'icons/module_web.gif','width="24" height="24"'),
	'MOD:web_layout/layout.gif'  => array($temp_eP.'icons/module_web_layout.gif','width="24" height="24"'),
	'MOD:web_view/view.gif'  => array($temp_eP.'icons/module_web_view.gif','width="24" height="24"'),
	'MOD:web_list/list.gif'  => array($temp_eP.'icons/module_web_list.gif','width="24" height="24"'),
	'MOD:web_info/info.gif'  => array($temp_eP.'icons/module_web_info.gif','width="24" height="24"'),
	'MOD:web_perm/perm.gif'  => array($temp_eP.'icons/module_web_perms.gif','width="24" height="24"'),
	'MOD:web_perm/legend.gif'  => array($temp_eP.'icons/legend.gif','width="24" height="24"'),
	'MOD:web_func/func.gif'  => array($temp_eP.'icons/module_web_func.gif','width="24" height="24"'),
	'MOD:web_ts/ts1.gif'  => array($temp_eP.'icons/module_web_ts.gif','width="24" height="24"'),
	'MOD:web_modules/modules.gif' => array($temp_eP.'icons/module_web_modules.gif','width="24" height="24"'),
	'MOD:file/file.gif'  => array($temp_eP.'icons/module_file.gif','width="22" height="24"'),
	'MOD:file_list/list.gif'  => array($temp_eP.'icons/module_file_list.gif','width="22" height="24"'),
	'MOD:file_images/images.gif'  => array($temp_eP.'icons/module_file_images.gif','width="22" height="22"'),
	'MOD:doc/document.gif'  => array($temp_eP.'icons/module_doc.gif','width="22" height="22"'),
	'MOD:user/user.gif'  => array($temp_eP.'icons/module_user.gif','width="22" height="22"'),
	'MOD:user_task/task.gif'  => array($temp_eP.'icons/module_user_taskcenter.gif','width="22" height="22"'),
	'MOD:user_setup/setup.gif'  => array($temp_eP.'icons/module_user_setup.gif','width="22" height="22"'),
	'MOD:tools/tool.gif'  => array($temp_eP.'icons/module_tools.gif','width="25" height="24"'),
	'MOD:tools_beuser/beuser.gif'  => array($temp_eP.'icons/module_tools_user.gif','width="24" height="24"'),
	'MOD:tools_em/em.gif'  => array($temp_eP.'icons/module_tools_em.gif','width="24" height="24"'),
	'MOD:tools_em/install.gif'  => array($temp_eP.'icons/module_tools_em.gif','width="24" height="24"'),
	'MOD:tools_dbint/db.gif'  => array($temp_eP.'icons/module_tools_dbint.gif','width="25" height="24"'),
	'MOD:tools_config/config.gif'  => array($temp_eP.'icons/module_tools_config.gif','width="24" height="24"'),
	'MOD:tools_install/install.gif'  => array($temp_eP.'icons/module_tools_install.gif','width="24" height="24"'),
	'MOD:tools_log/log.gif'  => array($temp_eP.'icons/module_tools_log.gif','width="24" height="24"'),
	'MOD:tools_txphpmyadmin/thirdparty_db.gif'  => array($temp_eP.'icons/module_tools_phpmyadmin.gif','width="24" height="24"'),
	'MOD:tools_isearch/isearch.gif' => array($temp_eP.'icons/module_tools_isearch.gif','width="24" height="24"'),
	'MOD:help/help.gif'  => array($temp_eP.'icons/module_help.gif','width="23" height="24"'),
	'MOD:help_about/info.gif'  => array($temp_eP.'icons/module_help_about.gif','width="25" height="24"'),
	'MOD:help_aboutmodules/aboutmodules.gif'  => array($temp_eP.'icons/module_help_aboutmodules.gif','width="24" height="24"'),
	));

	// Adding icon for photomarathon extensions' backend module, if enabled:
	if (t3lib_extMgm::isloaded('user_photomarathon'))	{
		$TBE_STYLES['skinImg']['MOD:web_uphotomarathon/tab_icon.gif'] = array($temp_eP.'icons/ext/user_photomarathon/tab_icon.gif','width="24" height="24"');
	}
	// Adding icon for templavoila extensions' backend module, if enabled:
	if (t3lib_extMgm::isloaded('templavoila'))	{
		$TBE_STYLES['skinImg']['MOD:web_txtemplavoilaM1/moduleicon.gif'] = array($temp_eP.'icons/ext/templavoila/mod1/moduleicon.gif','width="22" height="22"');
		$TBE_STYLES['skinImg']['MOD:web_txtemplavoilaM2/moduleicon.gif'] = array($temp_eP.'icons/ext/templavoila/mod1/moduleicon.gif','width="22" height="22"');
	}
	// Adding icon for extension manager' backend module, if enabled:
	$TBE_STYLES['skinImg']['MOD:tools_em/install.gif'] = array($temp_eP.'icons/ext/templavoila/mod1/moduleicon.gif','width="22" height="22"');
	$TBE_STYLES['skinImg']['MOD:tools_em/uninstall.gif'] = array($temp_eP.'icons/ext/templavoila/mod1/moduleicon.gif','width="22" height="22"');

	//print_a($TBE_STYLES,2);
}
?>