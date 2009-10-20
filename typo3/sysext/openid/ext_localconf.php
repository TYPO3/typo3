<?php
// Make sure that we are executed only from the inside of TYPO3
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

// Register OpenID authentication service with TYPO3
t3lib_extMgm::addService($_EXTKEY, 'auth' /* sv type */,  'tx_openid_sv1' /* sv key */,
	array(
		'title' => 'OpenID Authentication',
		'description' => 'OpenID authentication service for Frontend and Backend',
		'subtype' => 'getUserFE,authUserFE,getUserBE,authUserBE',
		'available' => true,
		'priority' => 75, // Must be higher than for tx_sv_auth (50) or tx_sv_auth will deny request unconditionally
		'quality' => 50,
		'os' => '',
		'exec' => '',
		'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_openid_sv1.php',
		'className' => 'tx_openid_sv1',
	)
);

// Register eID script that performs final FE user authentication. It will be called by the OpenID provider
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_openid'] = 'EXT:openid/class.tx_openid_eid.php';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['setup']['accessLevelCheck']['tx_openid_mod_setup'] = 'EXT:openid/class.tx_openid_mod_setup.php';

// Use popup window to refresh login instead of the AJAX relogin:
$TYPO3_CONF_VARS['BE']['showRefreshLoginPopup'] = 1;
?>