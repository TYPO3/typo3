<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE' || TYPO3_MODE === 'FE' && isset($GLOBALS['BE_USER'])) {

	// Register as a skin
	$GLOBALS['TBE_STYLES']['skins'][$_EXTKEY] = array(
		'name' => 't3skin'
	);

	// Support for other extensions to add own icons...
	$presetSkinImgs = is_array($GLOBALS['TBE_STYLES']['skinImg']) ? $GLOBALS['TBE_STYLES']['skinImg'] : array();
	$GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories']['sprites'] = 'EXT:t3skin/stylesheets/sprites/';

	// Setting the relative path to the extension in temp. variable:
	$temp_eP = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY);

	// Alternative dimensions for frameset sizes:
	// Left menu frame width
	$GLOBALS['TBE_STYLES']['dims']['leftMenuFrameW'] = 190;

	// Top frame height
	$GLOBALS['TBE_STYLES']['dims']['topFrameH'] = 45;

	// Default navigation frame width
	$GLOBALS['TBE_STYLES']['dims']['navFrameWidth'] = 280;

	// Setting up auto detection of alternative icons:
	$GLOBALS['TBE_STYLES']['skinImgAutoCfg'] = array(
		'absDir' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'icons/',
		'relDir' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'icons/',
		'forceFileExtension' => 'gif',
		// Force to look for PNG alternatives...
		'iconSizeWidth' => 16,
		'iconSizeHeight' => 16
	);

	// Changing icon for filemounts, needs to be done here as overwriting the original icon would also change the filelist tree's root icon
	$GLOBALS['TCA']['sys_filemounts']['ctrl']['iconfile'] = '_icon_ftp_2.gif';

	$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][1][2] = 'EXT:t3skin/images/icons/status/user-frontend.png';

	// Manual setting up of alternative icons. This is mainly for module icons which has a special prefix:
	$GLOBALS['TBE_STYLES']['skinImg'] = array_merge($presetSkinImgs, array(
		'gfx/ol/blank.gif' => array('clear.gif', 'width="18" height="16"'),
		'MOD:web/website.gif' => array($temp_eP . 'icons/module_web.gif', 'width="24" height="24"'),
		'MOD:web_ts/ts1.gif' => array($temp_eP . 'icons/module_web_ts.gif', 'width="24" height="24"'),
		'MOD:web_modules/modules.gif' => array($temp_eP . 'icons/module_web_modules.gif', 'width="24" height="24"'),
		'MOD:web_txversionM1/cm_icon.gif' => array($temp_eP . 'icons/module_web_version.gif', 'width="24" height="24"'),
		'MOD:file/file.gif' => array($temp_eP . 'icons/module_file.gif', 'width="22" height="24"'),
		'MOD:file_images/images.gif' => array($temp_eP . 'icons/module_file_images.gif', 'width="22" height="22"'),
		'MOD:user/user.gif' => array($temp_eP . 'icons/module_user.gif', 'width="22" height="22"'),
		'MOD:user_doc/document.gif' => array($temp_eP . 'icons/module_doc.gif', 'width="22" height="22"'),
		'MOD:tools/tool.gif' => array($temp_eP . 'icons/module_tools.gif', 'width="25" height="24"'),
		'MOD:tools_txphpmyadmin/thirdparty_db.gif' => array($temp_eP . 'icons/module_tools_phpmyadmin.gif', 'width="24" height="24"'),
		'MOD:help/help.gif' => array($temp_eP . 'icons/module_help.gif', 'width="23" height="24"'),
		'MOD:help_txtsconfighelpM1/moduleicon.gif' => array($temp_eP . 'icons/module_help_ts.gif', 'width="25" height="24"')
	));

	// extJS theme
	$GLOBALS['TBE_STYLES']['extJS']['theme'] = $temp_eP . 'extjs/xtheme-t3skin.css';
	$GLOBALS['TBE_STYLES']['stylesheets']['admPanel'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('t3skin') . 'stylesheets/standalone/admin_panel.css';
}
