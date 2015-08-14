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
		'path' => '/wizard/backend_layout',
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

	/** File- and folder-related routes */
	// Editing the contents of a file
	'file_edit' => [
		'path' => '/file/editcontent',
		'controller' => \TYPO3\CMS\Backend\Controller\File\EditFileController::class
	],

	// Create a new folder
	'file_newfolder' => [
		'path' => '/file/new',
		'controller' => \TYPO3\CMS\Backend\Controller\File\CreateFolderController::class
	],

	// Rename a file
	'file_rename' => [
		'path' => '/file/rename',
		'controller' => \TYPO3\CMS\Backend\Controller\File\RenameFileController::class
	],

	// Replace a file with a different one
	'file_replace' => [
		'path' => '/file/replace',
		'controller' => \TYPO3\CMS\Backend\Controller\File\ReplaceFileController::class
	],

	// Upload new files
	'file_upload' => [
		'path' => '/file/upload',
		'controller' => \TYPO3\CMS\Backend\Controller\File\FileUploadController::class
	],

	// Register login frameset
	'login_frameset' => [
		'path' => '/login/frame',
		'controller' => \TYPO3\CMS\Backend\Controller\LoginFramesetController::class
	],

	// Register record history module
	'record_history' => [
		'path' => '/record/history',
		'controller' => \TYPO3\CMS\Backend\Controller\ContentElement\ElementHistoryController::class
	],

	// Register new record
	'db_new' => [
		'path' => '/record/new',
		'controller' => \TYPO3\CMS\Backend\Controller\NewRecordController::class
	],

	// Register new content element module
	'new_content_element' => [
		'path' => '/record/content/new',
		'controller' => \TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController::class
	],

	// Register move element module
	'move_element' => [
		'path' => '/record/move',
		'controller' => \TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController::class
	],

	// Register show item module
	'show_item' => [
		'path' => '/record/info',
		'controller' => \TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController::class
	],

	// Register browser
	'browser' => [
		'path' => '/record/browse',
		'controller' => \TYPO3\CMS\Recordlist\Controller\ElementBrowserFramesetController::class
	],

	// Register dummy window
	'dummy' => [
		'path' => '/empty',
		'controller' => \TYPO3\CMS\Backend\Controller\DummyController::class
	],

];
