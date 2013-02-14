<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['felogin']);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:' . $_EXTKEY . '/flexform.xml', 'login');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', array(
	'LLL:EXT:cms/locallang_ttc.xlf:CType.I.10',
	'login',
	'i/tt_content_login.gif'
), 'mailform', 'after');
$GLOBALS['TCA']['tt_content']['types']['login']['showitem'] = '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
													--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
													--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.plugin,
													pi_flexform;;;;1-1-1,
													--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
													--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
													--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
													--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
													--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
													--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.behaviour,
													--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended';
// Adds the redirect field to the fe_groups table
$tempColumns = array(
	'felogin_redirectPid' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:felogin/locallang_db.xlf:felogin_redirectPid',
		'config' => array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'pages',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'wizards' => array(
				'suggest' => array(
					'type' => 'suggest'
				)
			)
		)
	)
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_groups', 'felogin_redirectPid;;;;1-1-1', '', 'after:TSconfig');
// Adds the redirect field and the forgotHash field to the fe_users-table
$tempColumns = array(
	'felogin_redirectPid' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:felogin/locallang_db.xlf:felogin_redirectPid',
		'config' => array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'pages',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'wizards' => array(
				'suggest' => array(
					'type' => 'suggest'
				)
			)
		)
	),
	'felogin_forgotHash' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:felogin/locallang_db.xlf:felogin_forgotHash',
		'config' => array(
			'type' => 'passthrough'
		)
	)
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'felogin_redirectPid;;;;1-1-1', '', 'after:TSconfig');
?>