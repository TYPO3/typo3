<?php
defined('TYPO3_MODE') or die();

// Prepare new columns for be_users table
$tempColumns = array(
	'tx_openid_openid' => array(
		'exclude' => 0,
		'label' => 'LLL:EXT:openid/locallang_db.xlf:be_users.tx_openid_openid',
		'config' => array(
			'type' => 'input',
			'size' => '30',
			// Requirement: unique (BE users are unique in the whole system)
			'eval' => 'trim,nospace,unique',
			'wizards' => array(
				'_PADDING' => 2,
				'0' => array(
					'type' => 'popup',
					'title' => 'Add OpenID',
					'module' => array(
						'name' => 'wizard_openid'
					),
					'icon' => 'EXT:openid/ext_icon.gif',
					'JSopenParams' => ',width=600,height=400,status=0,menubar=0,scrollbars=0',
				)
			),
		)
	)
);
// Add new columns to be_users table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_openid_openid;;;;1-1-1', '', 'after:username');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('be_users', 'EXT:openid' . '/locallang_csh.xlf');