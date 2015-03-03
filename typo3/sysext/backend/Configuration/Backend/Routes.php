<?php
use TYPO3\CMS\Backend\Controller as Controller;

/**
 * Definitions for routes provided by EXT:backend
 * Contains all "regular" routes for entry points
 *
 * Please note that this setup is preliminary until all core use-cases are set up here.
 * Especially some more properties regarding modules will be added until TYPO3 CMS 7 LTS, and might change.
 *
 * Currently the "access" property is only used so no token creation + validation is made,
 * but will be extended further.
 *
 * @internal This is not a public API yet until TYPO3 CMS 7 LTS.
 */
return [
	// Login screen of the TYPO3 Backend
	'login' => [
		'path' => '/login',
		'access' => 'public',
		'controller' => [
			Controller\LoginController::class,
			'indexAction'
		]
	],

	// Main backend rendering setup (backend.php) for the TYPO3 Backend
	'backend' => [
		'path' => '/main',
		'controller' => [
			Controller\BackendController::class,
			'render'
		]
	],

	// Logout script for the TYPO3 Backend
	'logout' => [
		'path' => '/logout',
		'controller' => [
			Controller\LogoutController::class,
			'logout'
		]
	]
];
