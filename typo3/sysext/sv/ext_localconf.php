<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Register base authentication service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY, 'auth', 'TYPO3\\CMS\\Sv\\AuthenticationService', array(
	'title' => 'User authentication',
	'description' => 'Authentication with username/password.',
	'subtype' => 'getUserBE,authUserBE,getUserFE,authUserFE,getGroupsFE,processLoginDataBE,processLoginDataFE',
	'available' => TRUE,
	'priority' => 50,
	'quality' => 50,
	'os' => '',
	'exec' => '',
	'className' => 'TYPO3\\CMS\\Sv\\AuthenticationService'
));
?>