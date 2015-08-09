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
		'controller' => Controller\LoginController::class
	],

	// Main backend rendering setup (backend.php) for the TYPO3 Backend
	'main' => [
		'path' => '/main',
		'controller' => Controller\BackendController::class
	],

	// Logout script for the TYPO3 Backend
	'logout' => [
		'path' => '/logout',
		'controller' => Controller\LogoutController::class
	],

	// Register backend_layout wizard
	'wizard_backend_layout' => [
		'path' => '/wizard/backendlayout',
		'controller' => \TYPO3\CMS\Backend\Controller\BackendLayoutWizardController::class
	],

	// Register colorpicker wizard
	'wizard_colorpicker' => [
		'path' => '/wizard/colorpicker',
		'controller' => \TYPO3\CMS\Backend\Controller\Wizard\ColorpickerController::class
	],

	// Register table wizard
	'wizard_table' => [
		'path' => '/wizard/table',
		'controller' => \TYPO3\CMS\Backend\Controller\Wizard\TableController::class
	],

	// Register rte wizard
	'wizard_rte' => [
		'path' => '/wizard/rte',
		'controller' => \TYPO3\CMS\Backend\Controller\Wizard\RteController::class
	],

	// Register add wizard
	'wizard_add' => [
		'path' => '/wizard/add',
		'controller' => \TYPO3\CMS\Backend\Controller\Wizard\AddController::class
	],

	// Register list wizard
	'wizard_list' => [
		'path' => '/wizard/list',
		'controller' => \TYPO3\CMS\Backend\Controller\Wizard\ListController::class
	],

	// Register edit wizard
	'wizard_edit' => [
		'path' => '/wizard/edit',
		'controller' => \TYPO3\CMS\Backend\Controller\Wizard\EditController::class
	],
];
