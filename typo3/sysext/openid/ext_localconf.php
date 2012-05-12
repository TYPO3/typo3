<?php
// Make sure that we are executed only from the inside of TYPO3
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Register OpenID authentication service with TYPO3
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY, 'auth', 'TYPO3\\CMS\\Openid\\OpenidService', array(
	'title' => 'OpenID Authentication',
	'description' => 'OpenID authentication service for Frontend and Backend',
	'subtype' => 'getUserFE,authUserFE,getUserBE,authUserBE',
	'available' => TRUE,
	'priority' => 75,
	// Must be higher than for tx_sv_auth (50) or tx_sv_auth will deny request unconditionally
	'quality' => 50,
	'os' => '',
	'exec' => '',
	'className' => 'TYPO3\\CMS\\Openid\\OpenidService'
));

// Register eID script that performs final FE user authentication. It will be called by the OpenID provider
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_openid'] = 'EXT:openid/class.tx_openid_eid.php';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['setup']['accessLevelCheck']['TYPO3\\CMS\\Openid\\OpenidModuleSetup'] = 'EXT:openid/class.tx_openid_mod_setup.php';
// Use popup window to refresh login instead of the AJAX relogin:
$TYPO3_CONF_VARS['BE']['showRefreshLoginPopup'] = 1;
?>