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

    // Expand or toggle in legacy database tree
    'sc_alt_db_navframe_expandtoggle' => [
        'path' => '/record/tree/expand',
        'target' => Controller\PageTreeNavigationController::class . '::ajaxExpandCollapse'
    ],

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

    // Search records
    'record_suggest' => [
        'path' => '/wizard/suggest/search',
        'target' => \TYPO3\CMS\Backend\Form\Wizard\SuggestWizard::class . '::searchAction'
    ],

    // Get shortcut edit form
    'shortcut_editform' => [
        'path' => '/shortcut/editform',
        'target' => \TYPO3\CMS\Backend\Backend\ToolbarItems\ShortcutToolbarItem::class . '::editFormAction'
    ],

    // Save edited shortcut
    'shortcut_saveform' => [
        'path' => '/shortcut/saveform',
        'target' => \TYPO3\CMS\Backend\Backend\ToolbarItems\ShortcutToolbarItem::class . '::saveFormAction'
    ],

    // Render shortcut toolbar item
    'shortcut_list' => [
        'path' => '/shortcut/list',
        'target' => \TYPO3\CMS\Backend\Backend\ToolbarItems\ShortcutToolbarItem::class . '::menuAction'
    ],

    // Delete a shortcut
    'shortcut_remove' => [
        'path' => '/shortcut/remove',
        'target' => \TYPO3\CMS\Backend\Backend\ToolbarItems\ShortcutToolbarItem::class . '::removeShortcutAction'
    ],

    // Create a new shortcut
    'shortcut_create' => [
        'path' => '/shortcut/create',
        'target' => \TYPO3\CMS\Backend\Backend\ToolbarItems\ShortcutToolbarItem::class . '::createShortcutAction'
    ],

    // Render systeminformtion toolbar item
    'systeminformation_render' => [
        'path' => '/system-information/render',
        'target' => \TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::class . '::renderMenuAction'
    ],

    // Reload the module menu
    'modulemenu' => [
        'path' => '/module-menu',
        'target' => Controller\BackendController::class . '::getModuleMenu'
    ],

    // Log in into backend
    'login' => [
        'path' => '/login',
        'target' => \TYPO3\CMS\Backend\AjaxLoginHandler::class . '::loginAction',
        'access' => 'public'
    ],

    // Log out from backend
    'logout' => [
        'path' => '/logout',
        'target' => \TYPO3\CMS\Backend\AjaxLoginHandler::class . '::logoutAction',
        'access' => 'public'
    ],

    // Refresh login of backend
    'login_refresh' => [
        'path' => '/login/refresh',
        'target' => \TYPO3\CMS\Backend\AjaxLoginHandler::class . '::refreshAction',
    ],

    // Check if backend session has timed out
    'login_timedout' => [
        'path' => '/login/timedout',
        'target' => \TYPO3\CMS\Backend\AjaxLoginHandler::class . '::isTimedOutAction',
        'access' => 'public'
    ],

    // ExtDirect routing
    'ext_direct_route' => [
        'path' => '/ext-direct/route',
        'target' => \TYPO3\CMS\Core\ExtDirect\ExtDirectRouter::class . '::routeAction',
        'access' => 'public'
    ],

    // ExtDirect API
    'ext_direct_api' => [
        'path' => '/ext-direct/api',
        'target' => \TYPO3\CMS\Core\ExtDirect\ExtDirectApi::class . '::getAPI'
    ],

    // Render flash messages
    'flashmessages_render' => [
        'path' => '/flashmessages/render',
        'target' => \TYPO3\CMS\Backend\Template\DocumentTemplate::class . '::renderQueuedFlashMessages'
    ],

    // Load context menu for
    'contextmenu' => [
        'path' => '/context-menu',
        'target' => Controller\ClickMenuController::class . '::getContextMenuAction'
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
        'target' => \TYPO3\CMS\Backend\Form\Wizard\ImageManipulationWizard::class . '::getWizardAction'
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
        'target' => \TYPO3\CMS\Core\Imaging\IconFactory::class . '::processAjaxRequest'
    ],

    // Encode typolink parts on demand
    'link_browser_encodetypolink' => [
        'path' => '/link-browser/encode-typolink',
        'target' => \TYPO3\CMS\Backend\Controller\LinkBrowserController::class . '::encodeTypoLink',
    ],

    // Get languages in page and colPos
    'languages_page_colpos' => [
        'path' => '/records/localize/get-languages',
        'target' => Controller\Page\LocalizationController::class . '::getUsedLanguagesInPageAndColumn'
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
    ]
];
