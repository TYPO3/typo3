<?php
use TYPO3\CMS\Backend\Controller;

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

	// Register login frameset
	'login_frameset' => [
		'path' => '/login/frame',
		'controller' => Controller\LoginFramesetController::class
	],

	/** Wizards */
	// Register backend_layout wizard
	'wizard_backend_layout' => [
		'path' => '/wizard/backend_layout',
		'controller' => Controller\BackendLayoutWizardController::class
	],

	// Register colorpicker wizard
	'wizard_colorpicker' => [
		'path' => '/wizard/colorpicker',
		'controller' => Controller\Wizard\ColorpickerController::class
	],

	// Register table wizard
	'wizard_table' => [
		'path' => '/wizard/table',
		'controller' => Controller\Wizard\TableController::class
	],

	// Register rte wizard
	'wizard_rte' => [
		'path' => '/wizard/rte',
		'controller' => Controller\Wizard\RteController::class
	],

	// Register add wizard
	'wizard_add' => [
		'path' => '/wizard/add',
		'controller' => Controller\Wizard\AddController::class
	],

	// Register list wizard
	'wizard_list' => [
		'path' => '/wizard/list',
		'controller' => Controller\Wizard\ListController::class
	],

	// Register edit wizard
	'wizard_edit' => [
		'path' => '/wizard/edit',
		'controller' => Controller\Wizard\EditController::class
	],


	/** File- and folder-related routes */

	// File navigation tree
	'file_navframe' => [
		'path' => '/folder/tree',
		'controller' => Controller\FileSystemNavigationFrameController::class
	],

	// Editing the contents of a file
	'file_edit' => [
		'path' => '/file/editcontent',
		'controller' => Controller\File\EditFileController::class
	],

	// Create a new folder
	'file_newfolder' => [
		'path' => '/file/new',
		'controller' => Controller\File\CreateFolderController::class
	],

	// Rename a file
	'file_rename' => [
		'path' => '/file/rename',
		'controller' => Controller\File\RenameFileController::class
	],

	// Replace a file with a different one
	'file_replace' => [
		'path' => '/file/replace',
		'controller' => Controller\File\ReplaceFileController::class
	],

	// Upload new files
	'file_upload' => [
		'path' => '/file/upload',
		'controller' => Controller\File\FileUploadController::class
	],

	/** DB Records-related routes */
	// Register record history module
	'record_history' => [
		'path' => '/record/history',
		'controller' => Controller\ContentElement\ElementHistoryController::class
	],

	// Register new record
	'db_new' => [
		'path' => '/record/new',
		'controller' => Controller\NewRecordController::class
	],

	// Register new content element module
	'new_content_element' => [
		'path' => '/record/content/new',
		'controller' => Controller\ContentElement\NewContentElementController::class
	],

	// Register move element module
	'move_element' => [
		'path' => '/record/move',
		'controller' => Controller\ContentElement\MoveElementController::class
	],

	// Register show item module
	'show_item' => [
		'path' => '/record/info',
		'controller' => Controller\ContentElement\ElementInformationController::class
	],

	// Register browser
	'browser' => [
		'path' => '/record/browse',
		'controller' => \TYPO3\CMS\Recordlist\Controller\ElementBrowserFramesetController::class
	],

	// Dummy document - displays nothing but background color.
	'dummy' => [
		'path' => '/empty',
		'controller' => Controller\DummyController::class
	],

	/** TYPO3 Core Engine-related routes */
	/**
	 * TCE gateway (TYPO3 Core Engine) for database handling
	 * This script is a gateway for POST forms to \TYPO3\CMS\Core\DataHandling\DataHandler
	 * that manipulates all information in the database!!
	 * For syntax and API information, see the document 'TYPO3 Core APIs'
	 */
	'tce_db' => [
		'path' => '/record/commit',
		'controller' => Controller\SimpleDataHandlerController::class
	],

	/**
	 * Gateway for TCE (TYPO3 Core Engine) file-handling through POST forms.
	 * This script serves as the fileadministration part of the TYPO3 Core Engine.
	 * Basically it includes two libraries which are used to manipulate files on the server.
	 *
	 * For syntax and API information, see the document 'TYPO3 Core APIs'
	 */
	'tce_file' => [
		'path' => '/file/commit',
		'controller' => Controller\File\FileController::class
	],

	/**
	 * Main form rendering script
	 * By sending certain parameters to this script you can bring up a form
	 * which allows the user to edit the content of one or more database records.
	 */
	'record_edit' => [
		'path' => '/record/edit',
		'controller' => Controller\EditDocumentController::class
	],
];
