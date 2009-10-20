<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// Add the service
t3lib_extMgm::addService($_EXTKEY,  'auth' /* sv type */,  'tx_rsaauth_sv1' /* sv key */,
	array(
		'title' => 'RSA authentication',
		'description' => 'Authenticates users by using encrypted passwords',

		'subtype' => 'getUserBE,authUserBE,getUserFE,authUserFE',

		'available' => TRUE,
		'priority' => 60,	// tx_svauth_sv1 has 50, t3sec_saltedpw has 55. This service must have higher priority!
		'quality' => 60,	// tx_svauth_sv1 has 50. This service must have higher quality!

		'os' => '',
		'exec' => '',		// Do not put a dependency on openssh here or service loading will fail!

		'classFile' => t3lib_extMgm::extPath($_EXTKEY) . 'sv1/class.tx_rsaauth_sv1.php',
		'className' => 'tx_rsaauth_sv1',
	)
);

// Add a hook to the BE login form
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginFormHook'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_rsaauth_loginformhook.php:tx_rsaauth_loginformhook->getLoginFormTag';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginScriptHook'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_rsaauth_loginformhook.php:tx_rsaauth_loginformhook->getLoginScripts';

// Add a hook to the FE login form (felogin system extension)
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_rsaauth_feloginhook.php:tx_rsaauth_feloginhook->loginFormHook';

// Add a hook to show Backend warnings
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_rsaauth_backendwarnings.php:tx_rsaauth_backendwarnings';

// Use popup window to refresh login instead of the AJAX relogin:
$TYPO3_CONF_VARS['BE']['showRefreshLoginPopup'] = 1;
?>