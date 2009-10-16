<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// Register base authentication service
t3lib_extMgm::addService($_EXTKEY,  'auth' /* sv type */,  'tx_sv_auth' /* sv key */,
		array(

			'title' => 'User authentication',
			'description' => 'Authentication with username/password.',

			'subtype' => 'getUserBE,authUserBE,getUserFE,authUserFE,getGroupsFE',

			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'class.tx_sv_auth.php',
			'className' => 'tx_sv_auth',
		)
	);

	// Add hooks to the backend login form
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginFormHook'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/class.tx_sv_loginformhook.php:tx_sv_loginformhook->getLoginFormTag';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginScriptHook'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/class.tx_sv_loginformhook.php:tx_sv_loginformhook->getLoginScripts';

?>