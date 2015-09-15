<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE' || TYPO3_MODE === 'FE' && isset($GLOBALS['BE_USER'])) {

	// Register as a skin
	$GLOBALS['TBE_STYLES']['skins']['t3skin'] = array(
		'name' => 't3skin',
		'stylesheetDirectories' => array(
			'sprites' => 'EXT:t3skin/stylesheets/sprites/',
			'css' => 'EXT:t3skin/Resources/Public/Css/'
		)
	);

	// Alternative dimensions for frameset sizes:
	// Left menu frame width
	$GLOBALS['TBE_STYLES']['dims']['leftMenuFrameW'] = 190;

	// Top frame height
	$GLOBALS['TBE_STYLES']['dims']['topFrameH'] = 45;

	// Setting up auto detection of alternative icons:
	$GLOBALS['TBE_STYLES']['skinImgAutoCfg'] = array(
		'absDir' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('t3skin') . 'icons/',
		'relDir' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3skin') . 'icons/',
		'forceFileExtension' => 'gif',
		// Force to look for PNG alternatives...
		'iconSizeWidth' => 16,
		'iconSizeHeight' => 16
	);

	// Changing icon for filemounts, needs to be done here as overwriting the original icon would also change the filelist tree's root icon
	$GLOBALS['TCA']['sys_filemounts']['ctrl']['iconfile'] = 'EXT:t3skin/icons/gfx/i/_icon_ftp_2.gif';

	$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][1][2] = 'EXT:t3skin/images/icons/status/user-frontend.png';

	// extJS theme
	$GLOBALS['TBE_STYLES']['extJS']['theme'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3skin') . 'extjs/xtheme-t3skin.css';
	$GLOBALS['TBE_STYLES']['stylesheets']['admPanel'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('t3skin') . 'stylesheets/standalone/admin_panel.css';
}
