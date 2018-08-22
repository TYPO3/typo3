<?php

use TYPO3\CMS\Backend\Controller;

/**
 * Definitions for routes provided by EXT:backend
 * Contains all AJAX-based routes for entry points
 *
 * Currently the "access" property is only used so no token creation + validation is made
 * but will be extended further.
 */
return [

    // Expand or toggle in legacy file tree
    'sc_alt_file_navframe_expandtoggle' => [
        'path' => '/folder/tree/expand',
        'target' => Controller\FileSystemNavigationFrameController::class . '::ajaxExpandCollapse'
    ],

    // File processing
    'file_process' => [
        'path' => '/file/process',
        'target' => Controller\File\FileController::class . '::processAjaxRequest'
    ],

    // Check if file exists
    'file_exists' => [
        'path' => '/file/exists',
        'target' => Controller\File\FileController::class . '::fileExistsInFolderAction'
    ],

    // Get record details of a child record in IRRE
    'record_inline_details' => [
        'path' => '/record/inline/details',
        'target' => Controller\FormInlineAjaxController::class . '::detailsAction'
    ],

    // Create new inline element
    'record_inline_create' => [
        'path' => '/record/inline/create',
        'target' => Controller\FormInlineAjaxController::class . '::createAction'
    ],

    // Synchronize localization
    'record_inline_synchronizelocalize' => [
        'path' => '/record/inline/synchronizelocalize',
        'target' => Controller\FormInlineAjaxController::class . '::synchronizeLocalizeAction'
    ],

    // Expand / Collapse inline record
    'record_inline_expandcollapse' => [
        'path' => '/record/inline/expandcollapse',
        'target' => Controller\FormInlineAjaxController::class . '::expandOrCollapseAction'
    ],

    // Site configuration inline create route
    'site_configuration_inline_create' => [
        'path' => '/siteconfiguration/inline/create',
        'target' => Controller\SiteInlineAjaxController::class . '::newInlineChildAction'
    ],

    // Validate slug input
    'record_slug_suggest' => [
        'path' => '/record/slug/suggest',
        'target' => Controller\FormSlugAjaxController::class . '::suggestAction'
    ],

    // Site configuration inline open existing "record" route
    'site_configuration_inline_details' => [
        'path' => '/siteconfiguration/inline/details',
        'target' => Controller\SiteInlineAjaxController::class . '::openInlineChildAction'
    ],

    // Add a flex form section container
    'record_flex_container_add' => [
        'path' => '/record/flex/containeradd',
        'target' => Controller\FormFlexAjaxController::class . '::containerAdd',
    ],

    // FormEngine suggest wizard result generator
    'record_suggest' => [
        'path' => '/wizard/suggest/search',
        'target' => \TYPO3\CMS\Backend\Controller\Wizard\SuggestWizardController::class . '::searchAction'
    ],

    // Fetch the tree-structured data from a record for the tree selection
    'record_tree_data' => [
        'path' => '/record/tree/fetchData',
        'target' => Controller\FormSelectTreeAjaxController::class . '::fetchDataAction'
    ],

    // Get data for page tree
    'page_tree_data' => [
        'path' => '/page/tree/fetchData',
        'target' => Controller\Page\TreeController::class . '::fetchDataAction'
    ],

    // Get page tree configuration
    'page_tree_configuration' => [
        'path' => '/page/tree/fetchConfiguration',
        'target' => Controller\Page\TreeController::class . '::fetchConfigurationAction'
    ],

    // Set temporary mount point
    'page_tree_set_temporary_mount_point' => [
        'path' => '/page/tree/setTemporaryMountPoint',
        'target' => Controller\Page\TreeController::class . '::setTemporaryMountPointAction'
    ],

    // Get shortcut edit form
    'shortcut_editform' => [
        'path' => '/shortcut/editform',
        'target' => Controller\ShortcutController::class . '::showEditFormAction'
    ],

    // Save edited shortcut
    'shortcut_saveform' => [
        'path' => '/shortcut/saveform',
        'target' => Controller\ShortcutController::class . '::updateAction'
    ],

    // Render shortcut toolbar item
    'shortcut_list' => [
        'path' => '/shortcut/list',
        'target' => Controller\ShortcutController::class . '::menuAction'
    ],

    // Delete a shortcut
    'shortcut_remove' => [
        'path' => '/shortcut/remove',
        'target' => Controller\ShortcutController::class . '::removeAction'
    ],

    // Create a new shortcut
    'shortcut_create' => [
        'path' => '/shortcut/create',
        'target' => Controller\ShortcutController::class . '::addAction'
    ],

    // Render systeminformtion toolbar item
    'systeminformation_render' => [
        'path' => '/system-information/render',
        'target' => \TYPO3\CMS\Backend\Controller\SystemInformationController::class . '::renderMenuAction',
        'parameters' => [
            'skipSessionUpdate' => 1
        ]
    ],

    // Reload the module menu
    'modulemenu' => [
        'path' => '/module-menu',
        'target' => Controller\BackendController::class . '::getModuleMenu'
    ],
    'topbar' => [
        'path' => '/topbar',
        'target' => Controller\BackendController::class . '::getTopbar'
    ],

    // Log in into backend
    'login' => [
        'path' => '/login',
        'target' => \TYPO3\CMS\Backend\Controller\AjaxLoginController::class . '::loginAction',
        'access' => 'public'
    ],

    // Log out from backend
    'logout' => [
        'path' => '/logout',
        'target' => \TYPO3\CMS\Backend\Controller\AjaxLoginController::class . '::logoutAction',
        'access' => 'public'
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
            'skipSessionUpdate' => 1
        ]
    ],

    // Render flash messages
    'flashmessages_render' => [
        'path' => '/flashmessages/render',
        'target' => \TYPO3\CMS\Backend\Controller\FlashMessageController::class . '::getQueuedFlashMessagesAction'
    ],

    // Load context menu for
    'contextmenu' => [
        'path' => '/context-menu',
        'target' => Controller\ContextMenuController::class . '::getContextMenuAction'
    ],

    // Load context menu for
    'contextmenu_clipboard' => [
        'path' => '/context-menu/clipboard',
        'target' => Controller\ContextMenuController::class . '::clipboardAction'
    ],

    // Process data handler commands
    'record_process' => [
        'path' => '/record/process',
        'target' => Controller\SimpleDataHandlerController::class . '::processAjaxRequest'
    ],

    // Process user settings
    'usersettings_process' => [
        'path' => '/usersettings/process',
        'target' => Controller\UserSettingsController::class . '::processAjaxRequest'
    ],

    // Open the image manipulation wizard
    'wizard_image_manipulation' => [
        'path' => '/wizard/image-manipulation',
        'target' => \TYPO3\CMS\Backend\Controller\Wizard\ImageManipulationController::class . '::getWizardContent'
    ],

    // Save a newly added online media
    'livesearch' => [
        'path' => '/livesearch',
        'target' => Controller\LiveSearchController::class . '::liveSearchAction'
    ],

    // Save a newly added online media
    'online_media_create' => [
        'path' => '/online-media/create',
        'target' => Controller\OnlineMediaController::class . '::createAction'
    ],

    // Get icon from IconFactory
    'icons' => [
        'path' => '/icons',
        'target' => \TYPO3\CMS\Core\Controller\IconController::class . '::getIcon'
    ],

    // Get icon cache identifier
    'icons_cache' => [
        'path' => '/icons/cache',
        'target' => \TYPO3\CMS\Core\Controller\IconController::class . '::getCacheIdentifier'
    ],

    // Encode typolink parts on demand
    'link_browser_encodetypolink' => [
        'path' => '/link-browser/encode-typolink',
        'target' => \TYPO3\CMS\Backend\Controller\LinkBrowserController::class . '::encodeTypoLink',
    ],

    // Get languages in page
    'page_languages' => [
        'path' => '/records/localize/get-languages',
        'target' => Controller\Page\LocalizationController::class . '::getUsedLanguagesInPage'
    ],

    // Get summary of records to localize
    'records_localize_summary' => [
        'path' => '/records/localize/summary',
        'target' => Controller\Page\LocalizationController::class . '::getRecordLocalizeSummary'
    ],

    // Localize the records
    'records_localize' => [
        'path' => '/records/localize',
        'target' => Controller\Page\LocalizationController::class . '::localizeRecords'
    ],

    // context help
    'context_help' => [
        'path' => '/context-help',
        'target' => \TYPO3\CMS\Backend\Controller\ContextHelpAjaxController::class . '::getHelpAction'
    ]
];
