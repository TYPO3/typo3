<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

/**
 * $GLOBALS['PAGES_TYPES'] defines the various types of pages (field: doktype) the system
 * can handle and what restrictions may apply to them.
 * Here you can define which tables are allowed on a certain pagetype (doktype)
 * NOTE: The 'default' entry in the $GLOBALS['PAGES_TYPES'] array is the 'base' for all
 * types, and for every type the entries simply overrides the entries in the 'default' type!
 */
$GLOBALS['PAGES_TYPES'] = [
    (string)PageRepository::DOKTYPE_BE_USER_SECTION => [
        'allowedTables' => '*',
    ],
    (string)PageRepository::DOKTYPE_SYSFOLDER => [
        //  Doktype 254 is a 'Folder' - a general purpose storage folder for whatever you like.
        // In CMS context it's NOT a viewable page. Can contain any element.
        'allowedTables' => '*',
    ],
    (string)PageRepository::DOKTYPE_RECYCLER => [
        // Doktype 255 is a recycle-bin.
        'allowedTables' => '*',
    ],
    'default' => [
        'allowedTables' => 'pages,sys_category,sys_file_reference,sys_file_collection',
        'onlyAllowedTables' => false,
    ],
];

/**
 * $TBE_MODULES contains the structure of the backend modules as they are
 * arranged in main- and sub-modules. Every entry in this array represents a
 * menu item on either first (key) or second level (value from list) in the
 * left menu in the TYPO3 backend
 * For information about adding modules to TYPO3 you should consult the
 * documentation found in "Inside TYPO3"
 */
ExtensionManagementUtility::addModule(
    'web',
    '',
    '',
    null,
    [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_web.xlf',
        'name' => 'web',
        'iconIdentifier' => 'modulegroup-web',
    ]
);
// workaround to add web->list by default
$GLOBALS['TBE_MODULES']['web'] = 'list';

ExtensionManagementUtility::addModule(
    'site',
    '',
    '',
    null,
    [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_site.xlf',
        'name' => 'site',
        'workspaces' => 'online',
        'iconIdentifier' => 'modulegroup-site',
    ]
);
ExtensionManagementUtility::addModule(
    'file',
    '',
    '',
    null,
    [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_file.xlf',
        'name' => 'file',
        'workspaces' => 'online,custom',
        'iconIdentifier' => 'modulegroup-file',
    ]
);
ExtensionManagementUtility::addModule(
    'user',
    '',
    '',
    null,
    [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_usertools.xlf',
        'name' => 'user',
        'iconIdentifier' => 'modulegroup-user',
    ]
);
ExtensionManagementUtility::addModule(
    'tools',
    '',
    '',
    null,
    [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_admintools.xlf',
        'name' => 'tools',
        'iconIdentifier' => 'modulegroup-tools',
    ]
);
ExtensionManagementUtility::addModule(
    'system',
    '',
    '',
    null,
    [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_system.xlf',
        'name' => 'system',
        'iconIdentifier' => 'modulegroup-system',
    ]
);
ExtensionManagementUtility::addModule(
    'help',
    '',
    '',
    null,
    [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_help.xlf',
        'name' => 'help',
        'iconIdentifier' => 'modulegroup-help',
    ]
);

// Register the page tree core navigation component
ExtensionManagementUtility::addCoreNavigationComponent('web', 'TYPO3/CMS/Backend/PageTree/PageTreeElement');

/**
 * $TBE_STYLES configures backend styles and colors; Basically this contains
 * all the values that can be used to create new skins for TYPO3.
 * For information about making skins to TYPO3 you should consult the
 * documentation found in "Inside TYPO3"
 */
$GLOBALS['TBE_STYLES'] = [];

/**
 * Setting up $TCA_DESCR - Context Sensitive Help (CSH)
 * For information about using the CSH API in TYPO3 you should consult the
 * documentation found in "Inside TYPO3"
 */
ExtensionManagementUtility::addLLrefForTCAdescr('pages', 'EXT:core/Resources/Private/Language/locallang_csh_pages.xlf');
ExtensionManagementUtility::addLLrefForTCAdescr('be_users', 'EXT:core/Resources/Private/Language/locallang_csh_be_users.xlf');
ExtensionManagementUtility::addLLrefForTCAdescr('be_groups', 'EXT:core/Resources/Private/Language/locallang_csh_be_groups.xlf');
ExtensionManagementUtility::addLLrefForTCAdescr('sys_filemounts', 'EXT:core/Resources/Private/Language/locallang_csh_sysfilem.xlf');
ExtensionManagementUtility::addLLrefForTCAdescr('sys_file_reference', 'EXT:core/Resources/Private/Language/locallang_csh_sysfilereference.xlf');
ExtensionManagementUtility::addLLrefForTCAdescr('sys_file_storage', 'EXT:core/Resources/Private/Language/locallang_csh_sysfilestorage.xlf');
ExtensionManagementUtility::addLLrefForTCAdescr('sys_language', 'EXT:core/Resources/Private/Language/locallang_csh_syslang.xlf');
ExtensionManagementUtility::addLLrefForTCAdescr('sys_news', 'EXT:core/Resources/Private/Language/locallang_csh_sysnews.xlf');
// General Core
ExtensionManagementUtility::addLLrefForTCAdescr('xMOD_csh_corebe', 'EXT:core/Resources/Private/Language/locallang_csh_corebe.xlf');
