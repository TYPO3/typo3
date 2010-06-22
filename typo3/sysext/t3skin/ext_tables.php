<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE' || (TYPO3_MODE == 'FE' && isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->isFrontendEditingActive())) {
	global $TBE_STYLES;

		// register as a skin
	$TBE_STYLES['skins'][$_EXTKEY] = array(
		'name' => 't3skin',
	);

		// Support for other extensions to add own icons...
	$presetSkinImgs = is_array($TBE_STYLES['skinImg']) ?
		$TBE_STYLES['skinImg'] :
		array();

	$TBE_STYLES['skins'][$_EXTKEY]['stylesheetDirectories']['sprites'] = 'EXT:t3skin/stylesheets/sprites/';

	/**
	 * Setting up backend styles and colors
	 */
	$TBE_STYLES['mainColors'] = array(	// Always use #xxxxxx color definitions!
		'bgColor'    => '#FFFFFF',		// Light background color
		'bgColor2'   => '#FEFEFE',		// Steel-blue
		'bgColor3'   => '#F1F3F5',		// dok.color
		'bgColor4'   => '#E6E9EB',		// light tablerow background, brownish
		'bgColor5'   => '#F8F9FB',		// light tablerow background, greenish
		'bgColor6'   => '#E6E9EB',		// light tablerow background, yellowish, for section headers. Light.
		'hoverColor' => '#FF0000',
		'navFrameHL' => '#F8F9FB'
	);

	$TBE_STYLES['colorschemes'][0] = '-|class-main1,-|class-main2,-|class-main3,-|class-main4,-|class-main5';
	$TBE_STYLES['colorschemes'][1] = '-|class-main11,-|class-main12,-|class-main13,-|class-main14,-|class-main15';
	$TBE_STYLES['colorschemes'][2] = '-|class-main21,-|class-main22,-|class-main23,-|class-main24,-|class-main25';
	$TBE_STYLES['colorschemes'][3] = '-|class-main31,-|class-main32,-|class-main33,-|class-main34,-|class-main35';
	$TBE_STYLES['colorschemes'][4] = '-|class-main41,-|class-main42,-|class-main43,-|class-main44,-|class-main45';
	$TBE_STYLES['colorschemes'][5] = '-|class-main51,-|class-main52,-|class-main53,-|class-main54,-|class-main55';

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

	$TBE_STYLES['borderschemes'][0] = array('', '', '', 'wrapperTable');
	$TBE_STYLES['borderschemes'][1] = array('', '', '', 'wrapperTable1');
	$TBE_STYLES['borderschemes'][2] = array('', '', '', 'wrapperTable2');
	$TBE_STYLES['borderschemes'][3] = array('', '', '', 'wrapperTable3');
	$TBE_STYLES['borderschemes'][4] = array('', '', '', 'wrapperTable4');
	$TBE_STYLES['borderschemes'][5] = array('', '', '', 'wrapperTable5');



		// Setting the relative path to the extension in temp. variable:
	$temp_eP = t3lib_extMgm::extRelPath($_EXTKEY);

		// Alternative dimensions for frameset sizes:
	$TBE_STYLES['dims']['leftMenuFrameW'] = 190;		// Left menu frame width
	$TBE_STYLES['dims']['topFrameH']      = 42;			// Top frame height
	$TBE_STYLES['dims']['navFrameWidth']  = 280;		// Default navigation frame width

		// Setting roll-over background color for click menus:
		// Notice, this line uses the the 'scriptIDindex' feature to override another value in this array (namely $TBE_STYLES['mainColors']['bgColor5']), for a specific script "typo3/alt_clickmenu.php"
	$TBE_STYLES['scriptIDindex']['typo3/alt_clickmenu.php']['mainColors']['bgColor5'] = '#dedede';

		// Setting up auto detection of alternative icons:
	$TBE_STYLES['skinImgAutoCfg'] = array(
		'absDir'             => t3lib_extMgm::extPath($_EXTKEY).'icons/',
		'relDir'             => t3lib_extMgm::extRelPath($_EXTKEY).'icons/',
		'forceFileExtension' => 'gif',	// Force to look for PNG alternatives...
#		'scaleFactor'        => 2/3,	// Scaling factor, default is 1
		'iconSizeWidth'      => 16,
		'iconSizeHeight'     => 16,
	);

		// Changing icon for filemounts, needs to be done here as overwriting the original icon would also change the filelist tree's root icon
	$TCA['sys_filemounts']['ctrl']['iconfile'] = '_icon_ftp_2.gif';

		// Manual setting up of alternative icons. This is mainly for module icons which has a special prefix:
	$TBE_STYLES['skinImg'] = array_merge($presetSkinImgs, array (
		'gfx/ol/blank.gif'                         => array('clear.gif','width="14" height="14"'),
		'MOD:web/website.gif'                      => array($temp_eP.'icons/module_web.gif','width="24" height="24"'),
		'MOD:web_layout/layout.gif'                => array($temp_eP.'icons/module_web_layout.gif','width="24" height="24"'),
		'MOD:web_view/view.gif'                    => array($temp_eP.'icons/module_web_view.png','width="24" height="24"'),
		'MOD:web_list/list.gif'                    => array($temp_eP.'icons/module_web_list.gif','width="24" height="24"'),
		'MOD:web_info/info.gif'                    => array($temp_eP.'icons/module_web_info.png','width="24" height="24"'),
		'MOD:web_perm/perm.gif'                    => array($temp_eP.'icons/module_web_perms.png','width="24" height="24"'),
		'MOD:web_func/func.gif'                    => array($temp_eP.'icons/module_web_func.png','width="24" height="24"'),
		'MOD:web_ts/ts1.gif'                       => array($temp_eP.'icons/module_web_ts.gif','width="24" height="24"'),
		'MOD:web_modules/modules.gif'              => array($temp_eP.'icons/module_web_modules.gif','width="24" height="24"'),
		'MOD:web_txversionM1/cm_icon.gif'          => array($temp_eP.'icons/module_web_version.gif','width="24" height="24"'),
		'MOD:file/file.gif'                        => array($temp_eP.'icons/module_file.gif','width="22" height="24"'),
		'MOD:file_list/list.gif'                   => array($temp_eP.'icons/module_file_list.gif','width="22" height="24"'),
		'MOD:file_images/images.gif'               => array($temp_eP.'icons/module_file_images.gif','width="22" height="22"'),
		'MOD:user/user.gif'                        => array($temp_eP.'icons/module_user.gif','width="22" height="22"'),
		'MOD:user_task/task.gif'                   => array($temp_eP.'icons/module_user_taskcenter.gif','width="22" height="22"'),
		'MOD:user_setup/setup.gif'                 => array($temp_eP.'icons/module_user_setup.gif','width="22" height="22"'),
		'MOD:user_doc/document.gif'                => array($temp_eP.'icons/module_doc.gif','width="22" height="22"'),
		'MOD:user_ws/sys_workspace.gif'            => array($temp_eP.'icons/module_user_ws.gif','width="22" height="22"'),
		'MOD:tools/tool.gif'                       => array($temp_eP.'icons/module_tools.gif','width="25" height="24"'),
		'MOD:tools_beuser/beuser.gif'              => array($temp_eP.'icons/module_tools_user.gif','width="24" height="24"'),
		'MOD:tools_em/em.gif'                      => array($temp_eP.'icons/module_tools_em.png','width="24" height="24"'),
		'MOD:tools_em/install.gif'                 => array($temp_eP.'icons/module_tools_em.gif','width="24" height="24"'),
		'MOD:tools_dbint/db.gif'                   => array($temp_eP.'icons/module_tools_dbint.gif','width="25" height="24"'),
		'MOD:tools_config/config.gif'              => array($temp_eP.'icons/module_tools_config.gif','width="24" height="24"'),
		'MOD:tools_install/install.gif'            => array($temp_eP.'icons/module_tools_install.gif','width="24" height="24"'),
		'MOD:tools_log/log.gif'                    => array($temp_eP.'icons/module_tools_log.gif','width="24" height="24"'),
		'MOD:tools_txphpmyadmin/thirdparty_db.gif' => array($temp_eP.'icons/module_tools_phpmyadmin.gif','width="24" height="24"'),
		'MOD:tools_isearch/isearch.gif'            => array($temp_eP.'icons/module_tools_isearch.gif','width="24" height="24"'),
		'MOD:help/help.gif'                        => array($temp_eP.'icons/module_help.gif','width="23" height="24"'),
		'MOD:help_about/info.gif'                  => array($temp_eP.'icons/module_help_about.gif','width="25" height="24"'),
		'MOD:help_aboutmodules/aboutmodules.gif'   => array($temp_eP.'icons/module_help_aboutmodules.gif','width="24" height="24"'),
		'MOD:help_cshmanual/about.gif'         => array($temp_eP.'icons/module_help_cshmanual.gif','width="25" height="24"'),
		'MOD:help_txtsconfighelpM1/moduleicon.gif' => array($temp_eP.'icons/module_help_ts.gif','width="25" height="24"'),
	));

		// Logo at login screen
	$TBE_STYLES['logo_login'] = $temp_eP . 'images/login/typo3logo-white-greyback.gif';

		// extJS theme
	$TBE_STYLES['extJS']['theme'] =  $temp_eP . 'extjs/xtheme-t3skin.css';

	// Adding HTML template for login screen
	$TBE_STYLES['htmlTemplates']['templates/login.html'] = 'sysext/t3skin/templates/login.html';

	$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = t3lib_extMgm::extPath('t3skin').'registerIe6Stylesheet.php';

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'][] = t3lib_extMgm::extPath('t3skin').'pngfix/class.tx_templatehook.php:tx_templatehook->registerPngFix';
}

?>