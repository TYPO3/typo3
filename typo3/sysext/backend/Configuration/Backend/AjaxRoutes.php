<?php

use TYPO3\CMS\Backend\Controller;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessLifetime;

/**
 * Definitions for routes provided by EXT:backend
 * Contains all AJAX-based routes for entry points
 *
 * Currently the "access" property is only used so no token creation + validation is made
 * but will be extended further.
 */
return [

    // Rename resource
    'resource_rename' => [
        'path' => '/resource/rename',
        'methods' => ['POST'],
        'target' => Controller\Resource\ResourceController::class . '::renameResourceAction',
    ],

    // Link resource
    'link_resource' => [
        'path' => '/link/resource',
        'methods' => ['POST'],
        'target' => Controller\LinkController::class . '::resourceAction',
    ],

    // File processing
    'file_process' => [
        'path' => '/file/process',
        'target' => Controller\File\FileController::class . '::processAjaxRequest',
    ],

    // Check if file exists
    'file_exists' => [
        'path' => '/file/exists',
        'target' => Controller\File\FileController::class . '::fileExistsInFolderAction',
    ],

    // Get details of a file reference in FormEngine
    'file_reference_details' => [
        'path' => '/file/reference/details',
        'target' => Controller\FormFilesAjaxController::class . '::detailsAction',
    ],

    // Create a new file reference in FormEngine
    'file_reference_create' => [
        'path' => '/file/reference/create',
        'methods' => ['POST'],
        'target' => Controller\FormFilesAjaxController::class . '::createAction',
    ],

    // Synchronize localization of a file reference in FormEngine
    'file_reference_synchronizelocalize' => [
        'path' => '/file/reference/synchronizelocalize',
        'methods' => ['POST'],
        'target' => Controller\FormFilesAjaxController::class . '::synchronizeLocalizeAction',
    ],

    // Expand / Collapse a file reference in FormEngine
    'file_reference_expandcollapse' => [
        'path' => '/file/reference/expandcollapse',
        'methods' => ['POST'],
        'target' => Controller\FormFilesAjaxController::class . '::expandOrCollapseAction',
    ],

    // Get record details of a child record in IRRE
    'record_inline_details' => [
        'path' => '/record/inline/details',
        'target' => Controller\FormInlineAjaxController::class . '::detailsAction',
    ],

    // Create new inline element
    'record_inline_create' => [
        'path' => '/record/inline/create',
        'target' => Controller\FormInlineAjaxController::class . '::createAction',
    ],

    // Synchronize localization
    'record_inline_synchronizelocalize' => [
        'path' => '/record/inline/synchronizelocalize',
        'target' => Controller\FormInlineAjaxController::class . '::synchronizeLocalizeAction',
    ],

    // Expand / Collapse inline record
    'record_inline_expandcollapse' => [
        'path' => '/record/inline/expandcollapse',
        'target' => Controller\FormInlineAjaxController::class . '::expandOrCollapseAction',
    ],

    // Site configuration inline create route
    'site_configuration_inline_create' => [
        'path' => '/siteconfiguration/inline/create',
        'target' => Controller\SiteInlineAjaxController::class . '::newInlineChildAction',
    ],

    // Validate slug input
    'record_slug_suggest' => [
        'path' => '/record/slug/suggest',
        'target' => Controller\FormSlugAjaxController::class . '::suggestAction',
    ],

    // Site configuration inline open existing "record" route
    'site_configuration_inline_details' => [
        'path' => '/siteconfiguration/inline/details',
        'target' => Controller\SiteInlineAjaxController::class . '::openInlineChildAction',
    ],

    // Add a flex form section container
    'record_flex_container_add' => [
        'path' => '/record/flex/containeradd',
        'target' => Controller\FormFlexAjaxController::class . '::containerAdd',
    ],

    // FormEngine suggest wizard result generator
    'record_suggest' => [
        'path' => '/wizard/suggest/search',
        'target' => \TYPO3\CMS\Backend\Controller\Wizard\SuggestWizardController::class . '::searchAction',
    ],

    // Fetch the tree-structured data from a record for the tree selection
    'record_tree_data' => [
        'path' => '/record/tree/fetchData',
        'target' => Controller\FormSelectTreeAjaxController::class . '::fetchDataAction',
    ],

    // Get data for page tree
    'page_tree_data' => [
        'path' => '/page/tree/fetchData',
        'target' => Controller\Page\TreeController::class . '::fetchDataAction',
    ],

    // Get rootline for page tree
    'page_tree_rootline' => [
        'path' => '/page/tree/fetchRootline',
        'target' => Controller\Page\TreeController::class . '::fetchRootlineAction',
    ],

    // Get data for page tree
    'page_tree_filter' => [
        'path' => '/page/tree/filterData',
        'target' => Controller\Page\TreeController::class . '::filterDataAction',
    ],

    // Get page tree configuration
    'page_tree_configuration' => [
        'path' => '/page/tree/fetchConfiguration',
        'target' => Controller\Page\TreeController::class . '::fetchConfigurationAction',
    ],

    // Get page tree configuration for element browser and link handler
    'page_tree_browser_configuration' => [
        'path' => '/browser/page/tree/fetchConfiguration',
        'target' => Controller\Page\TreeController::class . '::fetchReadOnlyConfigurationAction',
    ],

    // Set temporary mount point
    'page_tree_set_temporary_mount_point' => [
        'path' => '/page/tree/setTemporaryMountPoint',
        'target' => Controller\Page\TreeController::class . '::setTemporaryMountPointAction',
    ],

    // Get data for file storage tree
    'filestorage_tree_data' => [
        'path' => '/filestorage/tree/fetchData',
        'methods' => ['GET'],
        'target' => Controller\FileStorage\TreeController::class . '::fetchDataAction',
    ],

    // Get rootline for file storage tree
    'filestorage_tree_rootline' => [
        'path' => '/filestorage/tree/fetchRootline',
        'target' => Controller\FileStorage\TreeController::class . '::fetchRootlineAction',
    ],

    // Get filtered data for filestorage tree
    'filestorage_tree_filter' => [
        'path' => '/filestorage/tree/filterData',
        'methods' => ['GET'],
        'target' => Controller\FileStorage\TreeController::class . '::filterDataAction',
    ],

    // Get shortcut edit form
    'shortcut_editform' => [
        'path' => '/shortcut/editform',
        'target' => Controller\ShortcutController::class . '::showEditFormAction',
    ],

    // Save edited shortcut
    'shortcut_saveform' => [
        'path' => '/shortcut/saveform',
        'target' => Controller\ShortcutController::class . '::updateAction',
    ],

    // Render shortcut toolbar item
    'shortcut_list' => [
        'path' => '/shortcut/list',
        'target' => Controller\ShortcutController::class . '::menuAction',
    ],

    // Delete a shortcut
    'shortcut_remove' => [
        'path' => '/shortcut/remove',
        'target' => Controller\ShortcutController::class . '::removeAction',
    ],

    // Create a new shortcut
    'shortcut_create' => [
        'path' => '/shortcut/create',
        'target' => Controller\ShortcutController::class . '::addAction',
    ],

    // Render systeminformation toolbar item
    'systeminformation_render' => [
        'path' => '/system-information/render',
        'target' => \TYPO3\CMS\Backend\Controller\SystemInformationController::class . '::renderMenuAction',
        'parameters' => [
            'skipSessionUpdate' => 1,
        ],
    ],

    // Reload the module menu
    'modulemenu' => [
        'path' => '/module-menu',
        'target' => Controller\BackendController::class . '::getModuleMenu',
    ],
    'topbar' => [
        'path' => '/topbar',
        'target' => Controller\BackendController::class . '::getTopbar',
    ],

    // Log in into backend
    'login' => [
        'path' => '/login',
        'target' => \TYPO3\CMS\Backend\Controller\AjaxLoginController::class . '::loginAction',
        'access' => 'public',
    ],

    // Log out from backend
    'logout' => [
        'path' => '/logout',
        'target' => \TYPO3\CMS\Backend\Controller\AjaxLoginController::class . '::logoutAction',
        'access' => 'public',
    ],

    // Preflight check for login form
    'login_preflight' => [
        'path' => '/login/preflight',
        'target' => \TYPO3\CMS\Backend\Controller\AjaxLoginController::class . '::preflightAction',
        'access' => 'public',
    ],

    // Refresh login of backend
    'login_refresh' => [
        'path' => '/login/refresh',
        'target' => \TYPO3\CMS\Backend\Controller\AjaxLoginController::class . '::refreshAction',
    ],

    // Check if backend session has timed out
    'login_timedout' => [
        'path' => '/login/timedout',
        'target' => \TYPO3\CMS\Backend\Controller\AjaxLoginController::class . '::isTimedOutAction',
        'access' => 'public',
        'parameters' => [
            'skipSessionUpdate' => 1,
        ],
    ],

    // Switch user
    'switch_user' => [
        'path' => '/switch/user',
        'methods' => ['POST'],
        'target' => Controller\SwitchUserController::class . '::switchUserAction',
    ],
    'switch_user_exit' => [
        'path' => '/switch/user/exit',
        'methods' => ['POST'],
        'target' => Controller\SwitchUserController::class . '::exitSwitchUserAction',
    ],

    // Multi-factor authentication configuration
    'mfa' => [
        'path' => '/mfa',
        'target' => Controller\MfaAjaxController::class . '::handleRequest',
        'sudoMode' => [
            'group' => 'mfa',
            'lifetime' => AccessLifetime::medium,
        ],
    ],

    // Load context menu for
    'contextmenu' => [
        'path' => '/context-menu',
        'target' => Controller\ContextMenuController::class . '::getContextMenuAction',
    ],

    // Load context menu for
    'contextmenu_clipboard' => [
        'path' => '/context-menu/clipboard',
        'target' => Controller\ContextMenuController::class . '::clipboardAction',
    ],

    // Process data handler commands
    'record_process' => [
        'path' => '/record/process',
        'target' => Controller\SimpleDataHandlerController::class . '::processAjaxRequest',
    ],

    // Process user settings
    'usersettings_process' => [
        'path' => '/usersettings/process',
        'target' => Controller\UserSettingsController::class . '::processAjaxRequest',
    ],

    // Open the image manipulation wizard
    'wizard_image_manipulation' => [
        'path' => '/wizard/image-manipulation',
        'target' => \TYPO3\CMS\Backend\Controller\Wizard\ImageManipulationController::class . '::getWizardContent',
    ],

    // Search records
    'livesearch' => [
        'path' => '/livesearch/search',
        'target' => Controller\LiveSearchController::class . '::searchAction',
    ],

    // Get livesearch form
    'livesearch_form' => [
        'path' => '/livesearch/form',
        'target' => Controller\LiveSearchController::class . '::formAction',
    ],

    // Save a newly added online media
    'online_media_create' => [
        'path' => '/online-media/create',
        'target' => Controller\OnlineMediaController::class . '::createAction',
    ],

    // Get icon from IconFactory
    'icons' => [
        'path' => '/icons',
        'target' => \TYPO3\CMS\Core\Controller\IconController::class . '::getIcon',
    ],

    // Encode typolink parts on demand
    'link_browser_encodetypolink' => [
        'path' => '/link-browser/encode-typolink',
        'target' => \TYPO3\CMS\Backend\Controller\LinkBrowserController::class . '::encodeTypoLink',
    ],

    // Get languages in page
    'page_languages' => [
        'path' => '/records/localize/get-languages',
        'target' => Controller\Page\LocalizationController::class . '::getUsedLanguagesInPage',
    ],

    // Get summary of records to localize
    'records_localize_summary' => [
        'path' => '/records/localize/summary',
        'target' => Controller\Page\LocalizationController::class . '::getRecordLocalizeSummary',
    ],

    // Localize the records
    'records_localize' => [
        'path' => '/records/localize',
        'target' => Controller\Page\LocalizationController::class . '::localizeRecords',
    ],

    // column selector
    'show_columns' => [
        'path' => '/show/columns',
        'methods' => ['POST'],
        'target' => \TYPO3\CMS\Backend\Controller\ColumnSelectorController::class . '::updateVisibleColumnsAction',
    ],
    'show_columns_selector' => [
        'path' => '/show/columns/selector',
        'target' => \TYPO3\CMS\Backend\Controller\ColumnSelectorController::class . '::showColumnsSelectorAction',
    ],

    // Clear page cache in list module
    'web_list_clearpagecache' => [
        'path' => '/web/list/clearpagecache',
        'target' => \TYPO3\CMS\Backend\Controller\ClearPageCacheController::class . '::mainAction',
    ],

    // Record download in list module
    'record_download_settings' => [
        'path' => '/record/download/settings',
        'target' => \TYPO3\CMS\Backend\Controller\RecordListDownloadController::class . '::downloadSettingsAction',
    ],

    // Toggle record visibility
    'record_toggle_visibility' => [
        'path' => '/record/toggle-visibility',
        'methods' => ['POST'],
        'target' => \TYPO3\CMS\Backend\Controller\RecordListController::class . '::toggleRecordVisibilityAction',
    ],

    // Endpoint to generate a password
    'password_generate' => [
        'path' => '/password/generate',
        'target' => \TYPO3\CMS\Core\Controller\PasswordGeneratorController::class . '::generate',
    ],

    'security_csp_control' => [
        'access' => 'systemMaintainer',
        'path' => '/security/csp/control',
        'target' => \TYPO3\CMS\Backend\Security\ContentSecurityPolicy\CspAjaxController::class . '::handleRequest',
    ],

    'sudo_mode_control' => [
        'path' => '/sudo-mode/verify',
        'target' =>  Controller\Security\SudoModeController::class . '::verifyAction',
    ],

    // Get TSRef
    'codeeditor_tsref' => [
        'path' => '/code-editor/tsref',
        'target' => \TYPO3\CMS\Backend\Controller\CodeEditor\TypoScriptReferenceController::class . '::loadReference',
    ],

    // Load code completion templates
    'codeeditor_codecompletion_loadtemplates' => [
        'path' => '/code-editor/codecompletion/load-templates',
        'target' => \TYPO3\CMS\Backend\Controller\CodeEditor\CodeCompletionController::class . '::loadCompletions',
    ],

    'color_scheme_update' => [
        'path' => '/color-scheme/update',
        'target' => Controller\ColorSchemeController::class . '::updateAction',
    ],
];
