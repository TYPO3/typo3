<?php

declare(strict_types=1);

use TYPO3\CMS\Backend\Backend\ColorScheme;
use TYPO3\CMS\Backend\UserFunctions\UserSettingsItemsProcFunc;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

$GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
    'label' => 'backend.user_profile:user_settings',
    'config' => [
        'type' => 'json',
    ],
];

// Set up the showitem structure with tabs
$GLOBALS['TCA']['be_users']['columns']['user_settings']['showitem'] = '
    --div--;core.form.tabs:personaldata,
    --div--;core.form.tabs:account_security,
    --div--;core.form.tabs:backend_appearance,
    --div--;core.form.tabs:personalization';

// Personal data tab
ExtensionManagementUtility::addUserSetting(
    'realName',
    [
        'inheritFromParent' => true,
    ],
    'after:--div--;core.form.tabs:personaldata'
);
ExtensionManagementUtility::addUserSetting(
    'email',
    [
        'inheritFromParent' => true,
    ],
    'after:realName'
);
ExtensionManagementUtility::addUserSetting(
    'emailMeAtLogin',
    [
        'label' => 'backend.user_profile:email_me_at_login',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
        ],
    ],
    'after:email'
);
ExtensionManagementUtility::addUserSetting(
    'avatar',
    [
        'label' => 'backend.user_profile:avatar',
        'config' => [
            'type' => 'none',
            'renderType' => 'avatar',
        ],
    ],
    'after:emailMeAtLogin'
);
ExtensionManagementUtility::addUserSetting(
    'lang',
    [
        'label' => 'backend.user_profile:language',
        'table' => 'be_users',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'itemsProcFunc' => UserSettingsItemsProcFunc::class . '->addLanguageItems',
        ],
    ],
    'after:avatar'
);

// Account security tab
ExtensionManagementUtility::addUserSetting(
    'password',
    [
        'inheritFromParent' => true,
        'label' => 'backend.user_profile:new_password',
    ],
    'after:--div--;core.form.tabs:account_security'
);
ExtensionManagementUtility::addUserSetting(
    'password2',
    [
        'label' => 'backend.user_profile:new_password_again',
        'config' => [
            'type' => 'password',
            'passwordPolicy' => $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy'] ?? '',
            'size' => 20,
            'required' => true,
        ],
    ],
    'after:password'
);
ExtensionManagementUtility::addUserSetting(
    'mfaProviders',
    [
        'label' => 'backend.user_profile:mfa_providers',
        'config' => [
            // @todo Use a new internal TCA type to prevent raw data being displayed in the backend
            'type' => 'none',
            'renderType' => 'mfaInfo',
        ],
        'authenticationContext' => [
            'group' => 'be.userManagement',
        ],
    ],
    'after:password2'
);

// Backend appearance tab
ExtensionManagementUtility::addUserSetting(
    'colorScheme',
    [
        'label' => 'backend.messages:colorScheme',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => ColorScheme::getAvailableItemsForSelection(),
        ],
    ],
    'after:--div--;core.form.tabs:backend_appearance'
);
ExtensionManagementUtility::addUserSetting(
    'theme',
    [
        'label' => 'backend.messages:theme',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['label' => 'backend.messages:theme.fresh', 'value' => 'fresh'],
                ['label' => 'backend.messages:theme.modern', 'value' => 'modern'],
                ['label' => 'backend.messages:theme.classic', 'value' => 'classic'],
            ],
        ],
    ],
    'after:colorScheme'
);
ExtensionManagementUtility::addUserSetting(
    'startModule',
    [
        'label' => 'backend.user_profile:start_module',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'itemsProcFunc' => UserSettingsItemsProcFunc::class . '->renderStartModuleSelect',
            'items' => [],
        ],
    ],
    'after:theme'
);
ExtensionManagementUtility::addUserSetting(
    'backendTitleFormat',
    [
        'label' => 'backend.user_profile:backend_title_format',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['label' => 'backend.user_profile:backend_title_format.title_first', 'value' => 'titleFirst'],
                ['label' => 'backend.user_profile:backend_title_format.sitename_first', 'value' => 'sitenameFirst'],
            ],
        ],
    ],
    'after:startModule'
);
ExtensionManagementUtility::addUserSetting(
    'dateTimeFirstDayOfWeek',
    [
        'label' => 'backend.user_profile:datetime_first_day_of_week',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'itemsProcFunc' => UserSettingsItemsProcFunc::class . '->renderDateTimeFirstDayOfWeekSelect',
            'items' => [],
        ],
    ],
    'after:backendTitleFormat'
);

// Personalization tab
ExtensionManagementUtility::addUserSetting(
    'titleLen',
    [
        'label' => 'backend.user_profile:max_title_len',
        'config' => [
            'type' => 'number',
            'size' => 5,
            'range' => [
                'lower' => 10,
                'upper' => 255,
            ],
            'default' => 50,
        ],
    ],
    'after:--div--;core.form.tabs:personalization'
);
ExtensionManagementUtility::addUserSetting(
    'edit_docModuleUpload',
    [
        'label' => 'backend.user_profile:edit_doc_module_upload',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
        ],
    ],
    'after:titleLen'
);
ExtensionManagementUtility::addUserSetting(
    'showHiddenFilesAndFolders',
    [
        'label' => 'backend.user_profile:show_hidden_files_and_folders',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
        ],
    ],
    'after:edit_docModuleUpload'
);
ExtensionManagementUtility::addUserSetting(
    'displayRecentlyUsed',
    [
        'label' => 'backend.user_profile:display_recently_used',
        'persistentUpdate' => true,
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 1,
        ],
    ],
    'after:showHiddenFilesAndFolders'
);
ExtensionManagementUtility::addUserSetting(
    'contextualRecordEdit',
    [
        'label' => 'backend.user_profile:contextual_record_edit',
        'persistentUpdate' => true,
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 1,
        ],
    ],
    'after:edit_docModuleUpload'
);
ExtensionManagementUtility::addUserSetting(
    'copyLevels',
    [
        'label' => 'backend.user_profile:copy_levels',
        'config' => [
            'type' => 'number',
            'size' => 5,
            'range' => [
                'lower' => 0,
                'upper' => 100,
            ],
            'default' => 0,
        ],
    ],
    'after:displayRecentlyUsed'
);
