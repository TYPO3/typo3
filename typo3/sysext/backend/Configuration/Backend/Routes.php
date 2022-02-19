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
 */
return [
    // Login screen of the TYPO3 Backend
    'login' => [
        'path' => '/login',
        'access' => 'public',
        'target' => Controller\LoginController::class . '::formAction',
    ],

    // Main backend rendering setup (previously called backend.php) for the TYPO3 Backend
    'main' => [
        'path' => '/main',
        'referrer' => 'required,refresh-always',
        'target' => Controller\BackendController::class . '::mainAction',
    ],

    // IFrame dummy-url for browser-history state tracking of web component backend modules
    'state-tracker' => [
        'path' => '/state-tracker',
        'access' => 'public',
        'target' => Controller\StateTrackerController::class . '::mainAction',
    ],

    // Logout script for the TYPO3 Backend
    'logout' => [
        'path' => '/logout',
        'target' => Controller\LogoutController::class . '::logoutAction',
    ],
    // Show the password forgotten form for entering the email
    'password_forget' => [
        'path' => '/login/password-reset/forget',
        'access' => 'public',
        'target' => Controller\ResetPasswordController::class . '::forgetPasswordFormAction',
    ],
    // Send out the password reset email
    'password_forget_initiate_reset' => [
        'path' => '/login/password-reset/initiate-reset',
        'access' => 'public',
        'methods' => ['POST'],
        'target' => Controller\ResetPasswordController::class . '::initiatePasswordResetAction',
    ],
    'password_reset_validate' => [
        'path' => '/login/password-reset/validate',
        'access' => 'public',
        'target' => Controller\ResetPasswordController::class . '::passwordResetAction',
    ],
    'password_reset_finish' => [
        'path' => '/login/password-reset/finish',
        'access' => 'public',
        'methods' => ['POST'],
        'target' => Controller\ResetPasswordController::class . '::passwordResetFinishAction',
    ],

    // Register login frameset
    'login_frameset' => [
        'path' => '/login/frame',
        'access' => 'public',
        'target' => Controller\LoginController::class . '::refreshAction',
    ],

    // Authentication endpoint for Multi-factor authentication
    'auth_mfa' => [
        'path' => '/auth/mfa',
        'target' => Controller\MfaController::class . '::handleRequest',
    ],

    // Standalone setup endpoint for Multi-factor authentication
    'setup_mfa' => [
        'path' => '/setup/mfa',
        'target' => Controller\MfaSetupController::class . '::handleRequest',
    ],

    // Multi-factor authentication configuration
    'mfa' => [
        'path' => '/mfa',
        'target' => Controller\MfaConfigurationController::class . '::handleRequest',
    ],

    /** Wizards */
    // Register add wizard
    'wizard_add' => [
        'path' => '/wizard/add',
        'target' => Controller\Wizard\AddController::class . '::mainAction',
    ],

    // Register list wizard
    'wizard_list' => [
        'path' => '/wizard/list',
        'target' => Controller\Wizard\ListController::class . '::mainAction',
    ],

    // Register edit wizard
    'wizard_edit' => [
        'path' => '/wizard/edit',
        'target' => Controller\Wizard\EditController::class . '::mainAction',
    ],

    // Register link wizard
    'wizard_link' => [
        'path' => '/wizard/link/browse',
        'target' => Controller\LinkBrowserController::class . '::mainAction',
    ],

    /** File- and folder-related routes */

    // Add new online media
    'online_media' => [
        'path' => '/online-media',
        'target' => Controller\OnlineMediaController::class . '::mainAction',
    ],

    /** DB Records-related routes */
    // Register record history module
    'record_history' => [
        'path' => '/record/history',
        'target' => Controller\ContentElement\ElementHistoryController::class . '::mainAction',
    ],

    // Register new record
    'db_new' => [
        'path' => '/record/new',
        'target' => Controller\NewRecordController::class . '::mainAction',
        'redirect' => [
            'enable' => true,
            'parameters' => [
                'id' => true,
            ],
        ],
    ],

    'db_new_pages' => [
        'path' => '/record/new-page',
        'target' => Controller\NewRecordController::class . '::newPageAction',
        'redirect' => [
            'enable' => true,
            'parameters' => [
                'id' => true,
            ],
        ],
    ],

    // Register sort pages
    'pages_sort' => [
        'path' => '/pages/sort',
        'target' => Controller\Page\SortSubPagesController::class . '::mainAction',
    ],

    // Register create multiple pages
    'pages_new' => [
        'path' => '/pages/new',
        'target' => Controller\Page\NewMultiplePagesController::class . '::mainAction',
        'redirect' => [
            'enable' => true,
            'parameters' => [
                'id' => true,
            ],
        ],
    ],

    // Register new content element module (used in a modal)
    'new_content_element_wizard' => [
        'path' => '/record/content/wizard/new',
        'target' => Controller\ContentElement\NewContentElementController::class . '::handleRequest',
    ],

    // Register move element module
    'move_element' => [
        'path' => '/record/move',
        'target' => Controller\ContentElement\MoveElementController::class . '::mainAction',
    ],

    // Register show item module
    'show_item' => [
        'path' => '/record/info',
        'target' => Controller\ContentElement\ElementInformationController::class . '::mainAction',
    ],

    // Dummy document - displays nothing but background color.
    'dummy' => [
        'path' => '/empty',
        'target' => Controller\DummyController::class . '::mainAction',
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
        'target' => Controller\SimpleDataHandlerController::class . '::mainAction',
    ],

    /**
     * Gateway for TCE (TYPO3 Core Engine) file-handling through POST forms.
     * This script serves as the file administration part of the TYPO3 Core Engine.
     * Basically it includes two libraries which are used to manipulate files on the server.
     *
     * For syntax and API information, see the document 'TYPO3 Core APIs'
     */
    'tce_file' => [
        'path' => '/file/commit',
        'target' => Controller\File\FileController::class . '::mainAction',
    ],

    /**
     * Main form rendering script
     * By sending certain parameters to this script you can bring up a form
     * which allows the user to edit the content of one or more database records.
     */
    'record_edit' => [
        'path' => '/record/edit',
        'target' => Controller\EditDocumentController::class . '::mainAction',
        'redirect' => [
            'enable' => true,
            'parameters' => [
                'edit' => true,
            ],
        ],
    ],

    // Thumbnails
    'thumbnails' => [
        'path' => '/thumbnails',
        'target' => Controller\File\ThumbnailController::class . '::render',
    ],

    // Image processing
    'image_processing' => [
        'path' => '/image/process',
        'target' => Controller\File\ImageProcessController::class . '::process',
    ],

    // Clipboard processing
    'clipboard_process' => [
        'path' => '/clipboard/process',
        'methods' => ['POST'],
        'target' => Controller\ClipboardController::class . '::processRequest',
    ],

    // CSH Help popup
    'help_cshmanual_popup' => [
        'path' => '/help/cshmanual',
        'target' => Controller\HelpController::class . '::handleDetailPopup',
    ],
];
