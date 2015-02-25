<?php
defined('TYPO3_MODE') or die();

// Register base authentication service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
	'sv',
	'auth',
	\TYPO3\CMS\Sv\AuthenticationService::class,
	array(
		'title' => 'User authentication',
		'description' => 'Authentication with username/password.',
		'subtype' => 'getUserBE,authUserBE,getUserFE,authUserFE,getGroupsFE,processLoginDataBE,processLoginDataFE',
		'available' => TRUE,
		'priority' => 50,
		'quality' => 50,
		'os' => '',
		'exec' => '',
		'className' => \TYPO3\CMS\Sv\AuthenticationService::class
	)
);
// Add hooks to the backend login form
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginFormHook']['sv'] = \TYPO3\CMS\Sv\LoginFormHook::class . '->getLoginFormTag';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginScriptHook']['sv'] = \TYPO3\CMS\Sv\LoginFormHook::class . '->getLoginScripts';
