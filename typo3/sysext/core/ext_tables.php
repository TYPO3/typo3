<?php
defined('TYPO3_MODE') or die();

/**
 * $GLOBALS['PAGES_TYPES'] defines the various types of pages (field: doktype) the system
 * can handle and what restrictions may apply to them.
 * Here you can set the icon and especially you can define which tables are
 * allowed on a certain pagetype (doktype)
 * NOTE: The 'default' entry in the $GLOBALS['PAGES_TYPES'] array is the 'base' for all
 * types, and for every type the entries simply overrides the entries in the 'default' type!
 */
$GLOBALS['PAGES_TYPES'] = array(
    (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK => array(),
    (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT => array(),
    (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_BE_USER_SECTION => array(
        'type' => 'web',
        'allowedTables' => '*'
    ),
    (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT => array(),
    (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SPACER => array(
        'type' => 'sys'
    ),
    (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SYSFOLDER => array(
        //  Doktype 254 is a 'Folder' - a general purpose storage folder for whatever you like.
        // In CMS context it's NOT a viewable page. Can contain any element.
        'type' => 'sys',
        'allowedTables' => '*'
    ),
    (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_RECYCLER => array(
        // Doktype 255 is a recycle-bin.
        'type' => 'sys',
        'allowedTables' => '*'
    ),
    'default' => array(
        'type' => 'web',
        'allowedTables' => 'pages',
        'onlyAllowedTables' => '0'
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('sys_category,sys_file_reference,sys_file_collection');

/**
 * $TBE_MODULES contains the structure of the backend modules as they are
 * arranged in main- and sub-modules. Every entry in this array represents a
 * menu item on either first (key) or second level (value from list) in the
 * left menu in the TYPO3 backend
 * For information about adding modules to TYPO3 you should consult the
 * documentation found in "Inside TYPO3"
 */
$GLOBALS['TBE_MODULES'] = array(
    'web' => 'list',
    'file' => '',
    'user' => '',
    'tools' => '',
    'system' => '',
    'help' => '',
    '_configuration' => array(
        'web' => array(
            'labels' => 'LLL:EXT:lang/locallang_mod_web.xlf',
            'name' => 'web',
            'iconIdentifier' => 'module-web'
        ),
        'file' => array(
            'labels' => 'LLL:EXT:lang/locallang_mod_file.xlf',
            'navigationFrameModule' => 'file_navframe',
            'name' => 'file',
            'workspaces' => 'online,custom',
            'iconIdentifier' => 'module-file'
        ),
        'user' => array(
            'labels' => 'LLL:EXT:lang/locallang_mod_usertools.xlf',
            'name' => 'user',
            'iconIdentifier' => 'status-user-backend'
        ),
        'tools' => array(
            'labels' => 'LLL:EXT:lang/locallang_mod_admintools.xlf',
            'name' => 'tools',
            'iconIdentifier' => 'module-tools'
        ),
        'system' => array(
            'labels' => 'LLL:EXT:lang/locallang_mod_system.xlf',
            'name' => 'system',
            'iconIdentifier' => 'module-system'
        ),
        'help' => array(
            'labels' => 'LLL:EXT:lang/locallang_mod_help.xlf',
            'name' => 'help',
            'iconIdentifier' => 'module-help'
        )
    )
);

// Register the page tree core navigation component
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addCoreNavigationComponent('web', 'typo3-pagetree');

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
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('pages', 'EXT:lang/locallang_csh_pages.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('be_users', 'EXT:lang/locallang_csh_be_users.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('be_groups', 'EXT:lang/locallang_csh_be_groups.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('sys_filemounts', 'EXT:lang/locallang_csh_sysfilem.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('sys_language', 'EXT:lang/locallang_csh_syslang.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('sys_news', 'EXT:lang/locallang_csh_sysnews.xlf');
// General Core
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('xMOD_csh_corebe', 'EXT:lang/locallang_csh_corebe.xlf');
// Web > Info
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_info', 'EXT:lang/locallang_csh_web_info.xlf');
// Web > Func
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_func', 'EXT:lang/locallang_csh_web_func.xlf');
