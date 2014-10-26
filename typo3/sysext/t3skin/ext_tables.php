<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE' || TYPO3_MODE === 'FE' && isset($GLOBALS['BE_USER'])) {
	global $TBE_STYLES;

	// Register as a skin
	$TBE_STYLES['skins'][$_EXTKEY] = array(
		'name' => 't3skin'
	);

	// Support for other extensions to add own icons...
	$presetSkinImgs = is_array($TBE_STYLES['skinImg']) ? $TBE_STYLES['skinImg'] : array();
	$TBE_STYLES['skins'][$_EXTKEY]['stylesheetDirectories']['sprites'] = 'EXT:t3skin/stylesheets/sprites/';

	// Setting the relative path to the extension in temp. variable:
	$temp_eP = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY);

	// Alternative dimensions for frameset sizes:
	// Left menu frame width
	$TBE_STYLES['dims']['leftMenuFrameW'] = 190;

	// Top frame height
	$TBE_STYLES['dims']['topFrameH'] = 42;

	// Default navigation frame width
	$TBE_STYLES['dims']['navFrameWidth'] = 280;

	// Setting up auto detection of alternative icons:
	$TBE_STYLES['skinImgAutoCfg'] = array(
		'absDir' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'icons/',
		'relDir' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'icons/',
		'forceFileExtension' => 'gif',
		// Force to look for PNG alternatives...
		'iconSizeWidth' => 16,
		'iconSizeHeight' => 16
	);

	// Changing icon for filemounts, needs to be done here as overwriting the original icon would also change the filelist tree's root icon
	$TCA['sys_filemounts']['ctrl']['iconfile'] = '_icon_ftp_2.gif';

	// Adding flags to sys_language
	$TCA['sys_language']['ctrl']['typeicon_column'] = 'flag';
	$TCA['sys_language']['ctrl']['typeicon_classes'] = array(
		'default' => 'mimetypes-x-sys_language',
		'mask' => 'flags-###TYPE###'
	);
	$flagNames = array(
		'multiple',
		'ad',
		'ae',
		'af',
		'ag',
		'ai',
		'al',
		'am',
		'an',
		'ao',
		'ar',
		'as',
		'at',
		'au',
		'aw',
		'ax',
		'az',
		'ba',
		'bb',
		'bd',
		'be',
		'bf',
		'bg',
		'bh',
		'bi',
		'bj',
		'bm',
		'bn',
		'bo',
		'br',
		'bs',
		'bt',
		'bv',
		'bw',
		'by',
		'bz',
		'ca',
		'catalonia',
		'cc',
		'cd',
		'cf',
		'cg',
		'ch',
		'ci',
		'ck',
		'cl',
		'cm',
		'cn',
		'co',
		'cr',
		'cs',
		'cu',
		'cv',
		'cx',
		'cy',
		'cz',
		'de',
		'dj',
		'dk',
		'dm',
		'do',
		'dz',
		'ec',
		'ee',
		'eg',
		'eh',
		'england',
		'er',
		'es',
		'et',
		'europeanunion',
		'fam',
		'fi',
		'fj',
		'fk',
		'fm',
		'fo',
		'fr',
		'ga',
		'gb',
		'gd',
		'ge',
		'gf',
		'gh',
		'gi',
		'gl',
		'gm',
		'gn',
		'gp',
		'gq',
		'gr',
		'gs',
		'gt',
		'gu',
		'gw',
		'gy',
		'hk',
		'hm',
		'hn',
		'hr',
		'ht',
		'hu',
		'id',
		'ie',
		'il',
		'in',
		'io',
		'iq',
		'ir',
		'is',
		'it',
		'jm',
		'jo',
		'jp',
		'ke',
		'kg',
		'kh',
		'ki',
		'km',
		'kn',
		'kp',
		'kr',
		'kw',
		'ky',
		'kz',
		'la',
		'lb',
		'lc',
		'li',
		'lk',
		'lr',
		'ls',
		'lt',
		'lu',
		'lv',
		'ly',
		'ma',
		'mc',
		'md',
		'me',
		'mg',
		'mh',
		'mk',
		'ml',
		'mm',
		'mn',
		'mo',
		'mp',
		'mq',
		'mr',
		'ms',
		'mt',
		'mu',
		'mv',
		'mw',
		'mx',
		'my',
		'mz',
		'na',
		'nc',
		'ne',
		'nf',
		'ng',
		'ni',
		'nl',
		'no',
		'np',
		'nr',
		'nu',
		'nz',
		'om',
		'pa',
		'pe',
		'pf',
		'pg',
		'ph',
		'pk',
		'pl',
		'pm',
		'pn',
		'pr',
		'ps',
		'pt',
		'pw',
		'py',
		'qa',
		'qc',
		're',
		'ro',
		'rs',
		'ru',
		'rw',
		'sa',
		'sb',
		'sc',
		'scotland',
		'sd',
		'se',
		'sg',
		'sh',
		'si',
		'sj',
		'sk',
		'sl',
		'sm',
		'sn',
		'so',
		'sr',
		'st',
		'sv',
		'sy',
		'sz',
		'tc',
		'td',
		'tf',
		'tg',
		'th',
		'tj',
		'tk',
		'tl',
		'tm',
		'tn',
		'to',
		'tr',
		'tt',
		'tv',
		'tw',
		'tz',
		'ua',
		'ug',
		'um',
		'us',
		'uy',
		'uz',
		'va',
		'vc',
		've',
		'vg',
		'vi',
		'vn',
		'vu',
		'wales',
		'wf',
		'ws',
		'ye',
		'yt',
		'za',
		'zm',
		'zw'
	);
	foreach ($flagNames as $flagName) {
		$TCA['sys_language']['columns']['flag']['config']['items'][] = array($flagName, $flagName, 'EXT:t3skin/images/flags/' . $flagName . '.png');
	}

	$TCA['pages']['columns']['module']['config']['items'][1][2] = 'EXT:t3skin/images/icons/status/user-frontend.png';

	// Manual setting up of alternative icons. This is mainly for module icons which has a special prefix:
	$TBE_STYLES['skinImg'] = array_merge($presetSkinImgs, array(
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
	$TBE_STYLES['extJS']['theme'] = $temp_eP . 'extjs/xtheme-t3skin.css';
	$GLOBALS['TBE_STYLES']['stylesheets']['admPanel'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('t3skin') . 'stylesheets/standalone/admin_panel.css';
	$flagIcons = array();
	foreach ($flagNames as $flagName) {
		$flagIcons[] = 'flags-' . $flagName;
		$flagIcons[] = 'flags-' . $flagName . '-overlay';
	}
	\TYPO3\CMS\Backend\Sprite\SpriteManager::addIconSprite($flagIcons);
	unset($flagNames, $flagName, $flagIcons);
}
