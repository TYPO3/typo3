<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Add the service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY, 'auth', 'TYPO3\\CMS\\Rsaauth\\RsaAuthService', array(
	'title' => 'RSA authentication',
	'description' => 'Authenticates users by using encrypted passwords',
	'subtype' => 'processLoginDataBE,processLoginDataFE',
	'available' => TRUE,
	'priority' => 60,
	// tx_svauth_sv1 has 50, t3sec_saltedpw has 55. This service must have higher priority!
	'quality' => 60,
	// tx_svauth_sv1 has 50. This service must have higher quality!
	'os' => '',
	'exec' => '',
	// Do not put a dependency on openssh here or service loading will fail!
	'className' => 'TYPO3\\CMS\\Rsaauth\\RsaAuthService'
));

// Add a hook to the BE login form
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginFormHook'][$_EXTKEY] = 'TYPO3\\CMS\\Rsaauth\\Hook\\LoginFormHook->getLoginFormTag';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginScriptHook'][$_EXTKEY] = 'TYPO3\\CMS\\Rsaauth\\Hook\\LoginFormHook->getLoginScripts';
// Add hook for user setup module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['setupScriptHook'][$_EXTKEY] = 'TYPO3\\CMS\\Rsaauth\\Hook\\UserSetupHook->getLoginScripts';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['modifyUserDataBeforeSave'][$_EXTKEY] = 'TYPO3\\CMS\\Rsaauth\\Hook\\UserSetupHook->decryptPassword';
// Add a hook to the FE login form (felogin system extension)
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'][$_EXTKEY] = 'TYPO3\\CMS\\Rsaauth\\Hook\\FrontendLoginHook->loginFormHook';
// Add a hook to the FE login change password form (felogin system extension)
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['changePasswordFormOnSubmitFuncs'][$_EXTKEY] = 'TYPO3\\CMS\\Rsaauth\\Hook\\FrontendLoginHook->changePasswordFormOnSubmitHook';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['changePasswordFormModifyPostDataFuncs'][$_EXTKEY] = 'TYPO3\\CMS\\Rsaauth\\Hook\\FrontendLoginHook->changePasswordFormModifyPostDataHook';
// Add a hook to show Backend warnings
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'][$_EXTKEY] = 'TYPO3\\CMS\\Rsaauth\\BackendWarnings';
// Use popup window to refresh login instead of the AJAX relogin:
$TYPO3_CONF_VARS['BE']['showRefreshLoginPopup'] = 1;
?>