<?php

declare(strict_types=1);

use TYPO3\CMS\Backend\Backend\ColorScheme;
use TYPO3\CMS\Setup\Controller\SetupModuleController;

defined('TYPO3') or die();

$GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
    'label' => 'setup.messages:user_settings',
    'config' => [
        'type' => 'json',
    ],
    'columns' => [
        // Personal data tab - fields that inherit from be_users TCA
        'realName' => [
            'inheritFromParent' => true,
        ],
        'email' => [
            'inheritFromParent' => true,
        ],
        'emailMeAtLogin' => [
            'label' => 'setup.messages:emailMeAtLogin',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'avatar' => [
            'inheritFromParent' => true,
        ],
        'lang' => [
            'label' => 'setup.messages:language',
            'table' => 'be_users',
            'config' => [
                'type' => 'language',
            ],
        ],

        // Account security tab
        'password' => [
            'inheritFromParent' => true,
            'label' => 'setup.messages:newPassword',
        ],
        'password2' => [
            'inheritFromParent' => true,
            'label' => 'setup.messages:newPasswordAgain',
        ],
        'mfaProviders' => [
            'label' => 'setup.messages:mfaProviders',
            'config' => [
                'type' => 'mfa',
            ],
        ],

        // Backend appearance tab
        'colorScheme' => [
            'label' => 'backend.messages:colorScheme',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => ColorScheme::getAvailableItemsForSelection(),
            ],
        ],
        'theme' => [
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
        'startModule' => [
            'label' => 'setup.messages:startModule',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => SetupModuleController::class . '->renderStartModuleSelect',
                'items' => [],
            ],
        ],
        'backendTitleFormat' => [
            'label' => 'setup.messages:backendTitleFormat',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'setup.messages:backendTitleFormat.titleFirst', 'value' => 'titleFirst'],
                    ['label' => 'setup.messages:backendTitleFormat.sitenameFirst', 'value' => 'sitenameFirst'],
                ],
            ],
        ],
        'dateTimeFirstDayOfWeek' => [
            'label' => 'setup.messages:datetime_first_day_of_week',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => SetupModuleController::class . '->renderDateTimeFirstDayOfWeekSelect',
                'items' => [],
            ],
        ],

        // Personalization tab
        'titleLen' => [
            'label' => 'setup.messages:maxTitleLen',
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
        'edit_docModuleUpload' => [
            'label' => 'setup.messages:edit_docModuleUpload',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'showHiddenFilesAndFolders' => [
            'label' => 'setup.messages:showHiddenFilesAndFolders',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'displayRecentlyUsed' => [
            'label' => 'setup.messages:displayRecentlyUsed',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'copyLevels' => [
            'label' => 'setup.messages:copyLevels',
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
    ],
    'showitem' => '
        --div--;core.form.tabs:personaldata, realName, email, emailMeAtLogin, avatar, lang,
        --div--;core.form.tabs:account_security, password, password2, mfaProviders,
        --div--;core.form.tabs:backend_appearance, colorScheme, theme, startModule, backendTitleFormat, dateTimeFirstDayOfWeek,
        --div--;core.form.tabs:personalization, titleLen, edit_docModuleUpload, showHiddenFilesAndFolders, displayRecentlyUsed, copyLevels',
];
