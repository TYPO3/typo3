<?php
// Make sure that we are executed only from the inside of TYPO3
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Prepare new columns for be_users table
$tempColumns = array(
	'tx_openid_openid' => array(
		'exclude' => 0,
		'label' => 'LLL:EXT:openid/locallang_db.xml:be_users.tx_openid_openid',
		'config' => array(
			'type' => 'input',
			'size' => '30',
			// Requirement: unique (BE users are unique in the whole system)
			'eval' => 'trim,nospace,unique',
			'wizards' => Array(
				'_PADDING' => 2,
				'0' => Array(
					'type' => 'popup',
					'title' => 'Add OpenID',
					'script' => 'EXT:openid/wizard/index.php',
					'icon' => 'EXT:openid/ext_icon.gif',
					'JSopenParams' => ',width=600,height=400,status=0,menubar=0,scrollbars=0',
				)
			),
		)
	)
);
// Add new columns to be_users table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns, FALSE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_openid_openid;;;;1-1-1', '', 'after:username');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('be_users', 'EXT:' . $_EXTKEY . '/locallang_csh.xml');
// Prepare new columns for fe_users table
$tempColumns['tx_openid_openid']['config']['eval'] = 'trim,nospace,uniqueInPid';
// Add new columns to fe_users table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumns, FALSE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField('fe_users', 'username', 'tx_openid_openid');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('fe_users', 'EXT:' . $_EXTKEY . '/locallang_csh.xml');
// Add field to setup module
$GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_openid_openid'] = array(
	'type' => 'user',
	'table' => 'be_users',
	'label' => 'LLL:EXT:openid/locallang_db.xml:_MOD_user_setup.tx_openid_openid',
	'csh' => 'tx_openid_openid',
	'userFunc' => 'TYPO3\\CMS\\Openid\\OpenidModuleSetup->renderOpenID',
	'access' => 'TYPO3\\CMS\\Openid\\OpenidModuleSetup'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings('tx_openid_openid', 'after:password2');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_user_setup', 'EXT:openid/locallang_csh_mod.xml');

if (TYPO3_MODE == 'BE') {
	$iconPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/';
	\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(
		array(
			'large-aol'      => $iconPath . 'large/aol.png',
			'large-google'   => $iconPath . 'large/google.png',
			'large-mailru'   => $iconPath . 'large/mailru.png',
			'large-myopenid' => $iconPath . 'large/myopenid.png',
			'large-verisign' => $iconPath . 'large/verisign.png',
			'large-yahoo'    => $iconPath . 'large/yahoo.png',
			'large-yandex'   => $iconPath . 'large/yandex.png',

			'small-aol'         => $iconPath . 'small/aol.png',
			'small-blogger'     => $iconPath . 'small/blogger.png',
			'small-claimid'     => $iconPath . 'small/claimid.png',
			'small-clickpass'   => $iconPath . 'small/clickpass.png',
			'small-google'      => $iconPath . 'small/google.png',
			'small-launchpad'   => $iconPath . 'small/launchpad.png',
			'small-livejournal' => $iconPath . 'small/livejournal.png',
			'small-mailru'      => $iconPath . 'small/mailru.png',
			'small-myopenid'    => $iconPath . 'small/myopenid.png',
			'small-verisign'    => $iconPath . 'small/verisign.png',
			'small-wordpress'   => $iconPath . 'small/wordpress.png',
			'small-yahoo'       => $iconPath . 'small/yahoo.png',
			'small-yandex'      => $iconPath . 'small/yandex.png',
		),
		$_EXTKEY
	);

    //list of known openid providers. extension configuration determines
    //which provider is shown
    $TYPO3_CONF_VARS['SVCONF']['auth']['tx_openid']['providers'] = array(
        'blogger' => array(
            'name'      => 'Blogger',
            'url'       => 'http://{username}.blogspot.com/',
        ),
        'claimid' => array(
            'name'      => 'ClaimID',
            'url'       => 'http://claimid.com/{username}',
        ),
        'clickpass' => array(
            'name'      => 'ClickPass',
            'url'       => 'http://clickpass.com/public/{username}',
        ),
        'google' => array(
            'name'      => 'Google',
            'url'       => 'https://www.google.com/accounts/o8/id',
        ),
        'launchpad' => array(
            'name'      => 'Launchpad',
            'url'       => 'https://launchpad.net/~{username}',
        ),
        'myopenid' => array(
            'name'      => 'MyOpenID',
            'url'       => 'http://{username}.myopenid.com/',
        ),
        'verisign' => array(
            'name'      => 'Verisign',
            'url'       => 'http://{username}.pip.verisignlabs.com/',
        ),
        'wordpress' => array(
            'name'      => 'Wordpress',
            'url'       => 'http://{username}.wordpress.com/',
        ),
        'yahoo' => array(
            'name'      => 'Yahoo',
            'url'       => 'http://me.yahoo.com/',
        ),
    );
}
?>