<?php

return [
    'ctrl' => [
        'label' => 'title',
        'descriptionColumn' => 'rowDescription',
        'tstamp' => 'tstamp',
        'sortby' => 'sorting',
        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:pages',
        'type' => 'doktype',
        'versioningWS' => true,
        'delete' => 'deleted',
        'crdate' => 'crdate',
        'hideAtCopy' => true,
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'editlock' => 'editlock',
        'useColumnsForDefaultValues' => 'doktype,fe_group,hidden',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'typeicon_column' => 'doktype',
        'typeicon_classes' => [
            '1' => 'apps-pagetree-page-default',
            '1-hideinmenu' => 'apps-pagetree-page-hideinmenu',
            '1-root' => 'apps-pagetree-page-domain',
            '3' => 'apps-pagetree-page-shortcut-external',
            '3-hideinmenu' => 'apps-pagetree-page-shortcut-external-hideinmenu',
            '3-root' => 'apps-pagetree-page-shortcut-external-root',
            '4' => 'apps-pagetree-page-shortcut',
            '4-hideinmenu' => 'apps-pagetree-page-shortcut-hideinmenu',
            '4-root' => 'apps-pagetree-page-shortcut-root',
            '6' => 'apps-pagetree-page-backend-users',
            '6-hideinmenu' => 'apps-pagetree-page-backend-users-hideinmenu',
            '6-root' => 'apps-pagetree-page-backend-users-root',
            '7' => 'apps-pagetree-page-mountpoint',
            '7-hideinmenu' => 'apps-pagetree-page-mountpoint-hideinmenu',
            '7-root' => 'apps-pagetree-page-mountpoint-root',
            '199' => 'apps-pagetree-spacer',
            '199-hideinmenu' => 'apps-pagetree-spacer-hideinmenu',
            '199-root' => 'apps-pagetree-page-domain',
            '254' => 'apps-pagetree-folder-default',
            '254-hideinmenu' => 'apps-pagetree-folder-default',
            '254-root' => 'apps-pagetree-page-domain',
            'contains-shop' => 'apps-pagetree-folder-contains-shop',
            'contains-approve' => 'apps-pagetree-folder-contains-approve',
            'contains-fe_users' => 'apps-pagetree-folder-contains-fe_users',
            'contains-board' => 'apps-pagetree-folder-contains-board',
            'contains-news' => 'apps-pagetree-folder-contains-news',
            'page-contentFromPid' => 'apps-pagetree-page-content-from-page',
            'page-contentFromPid-root' => 'apps-pagetree-page-content-from-page-root',
            'page-contentFromPid-hideinmenu' => 'apps-pagetree-page-content-from-page-hideinmenu',
            'default' => 'apps-pagetree-page-default',
        ],
    ],
    'columns' => [
        'doktype' => [
            'exclude' => true,
            'label' => 'core.db.pages:doktype',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:doktype.I.0',
                        'value' => (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT,
                        'icon' => 'apps-pagetree-page-default',
                        'group' => 'default',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype.I.4',
                        'value' => (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_BE_USER_SECTION,
                        'icon' => 'apps-pagetree-page-backend-users',
                        'group' => 'default',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype.I.2',
                        'value' => (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_SHORTCUT,
                        'icon' => 'apps-pagetree-page-shortcut',
                        'group' => 'link',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype.I.5',
                        'value' => (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_MOUNTPOINT,
                        'icon' => 'apps-pagetree-page-mountpoint',
                        'group' => 'link',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype.I.8',
                        'value' => (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_LINK,
                        'icon' => 'apps-pagetree-page-shortcut-external',
                        'group' => 'link',
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:doktype.I.folder',
                        'value' => (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_SYSFOLDER,
                        'icon' => 'apps-pagetree-folder-default',
                        'group' => 'special',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype.I.7',
                        'value' => (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_SPACER,
                        'icon' => 'apps-pagetree-spacer',
                        'group' => 'special',
                    ],
                ],
                'itemGroups' => [
                    'default' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype.div.page',
                    'link' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype.div.link',
                    'special' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype.div.special',
                ],
                'default' => (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT,
            ],
        ],
        'title' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'core.db.pages:title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'slug' => [
            'label' => 'core.db.pages:slug',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['title'],
                    'fieldSeparator' => '/',
                    'prefixParentPageSlug' => true,
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
                'default' => '',
            ],
        ],
        'TSconfig' => [
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:tsconfig',
            'displayCond' => 'HIDE_FOR_NON_ADMINS',
            'config' => [
                'type' => 'text',
                'renderType' => 'codeEditor',
                'format' => 'typoscript',
                'cols' => 40,
                'rows' => 15,
                'enableTabulator' => true,
                'fixedFont' => true,
            ],
        ],
        'php_tree_stop' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:php_tree_stop',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'categories' => [
            'config' => [
                'type' => 'category',
            ],
        ],
        'layout' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:layout',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value',
                        'value' => '0',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.layout.I.1',
                        'value' => '1',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.layout.I.2',
                        'value' => '2',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.layout.I.3',
                        'value' => '3',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'extendToSubpages' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:extend_to_subpages',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'nav_title' => [
            'exclude' => true,
            'label' => 'core.db.pages:nav_title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'nav_hide' => [
            'exclude' => true,
            'label' => 'core.db.pages:nav_hide',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'subtitle' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'core.db.pages:subtitle',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'target' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:target',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 80,
                'valuePicker' => [
                    'items' => [
                        [ 'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:target.I.1', 'value' => '_blank' ],
                    ],
                ],
                'eval' => 'trim',
            ],
        ],
        'url' => [
            'label' => 'core.db.pages:url',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'required' => true,
                'eval' => 'trim',
                'softref' => 'url',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'lastUpdated' => [
            'exclude' => true,
            'label' => 'core.db.pages:last_updated',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'newUntil' => [
            'exclude' => true,
            'label' => 'core.db.pages:new_until',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'cache_timeout' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:cache_timeout',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value',
                        'value' => 0,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.cache_timeout.I.1',
                        'value' => 60,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.cache_timeout.I.2',
                        'value' => 300,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.cache_timeout.I.3',
                        'value' => 900,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.cache_timeout.I.4',
                        'value' => 1800,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.cache_timeout.I.5',
                        'value' => 3600,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.cache_timeout.I.6',
                        'value' => 14400,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.cache_timeout.I.7',
                        'value' => 86400,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.cache_timeout.I.8',
                        'value' => 172800,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.cache_timeout.I.9',
                        'value' => 604800,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.cache_timeout.I.10',
                        'value' => 2678400,
                    ],
                ],
                'default' => 0,
            ],
        ],
        'cache_tags' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:cache_tags',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ],
        ],
        'no_search' => [
            'exclude' => true,
            'label' => 'core.db.pages:no_search',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'shortcut' => [
            'label' => 'core.db.pages:shortcut',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 1,
                'relationship' => 'manyToOne',
                'suggestOptions' => [
                    'default' => [
                        'additionalSearchFields' => 'nav_title, url',
                        'addWhere' => ' AND pages.uid != ###THIS_UID###',
                    ],
                ],
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'shortcut_mode' => [
            'exclude' => true,
            'label' => 'core.db.pages:shortcut_mode',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.shortcut_mode.I.0',
                        'value' => \TYPO3\CMS\Core\Domain\Repository\PageRepository::SHORTCUT_MODE_NONE,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.shortcut_mode.I.1',
                        'value' => \TYPO3\CMS\Core\Domain\Repository\PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.shortcut_mode.I.3',
                        'value' => \TYPO3\CMS\Core\Domain\Repository\PageRepository::SHORTCUT_MODE_PARENT_PAGE,
                    ],
                ],
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'content_from_pid' => [
            'exclude' => true,
            'label' => 'core.db.pages:content_from_pid',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 1,
                'relationship' => 'manyToOne',
                'suggestOptions' => [
                    'default' => [
                        'additionalSearchFields' => 'nav_title, url',
                        'addWhere' => ' AND pages.uid != ###THIS_UID###',
                    ],
                ],
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'mount_pid' => [
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:mount_pid',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 1,
                'relationship' => 'manyToOne',
                'default' => 0,
            ],
        ],
        'keywords' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'core.db.pages:keywords',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'description' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'core.db.pages:description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'abstract' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'core.db.pages:abstract',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'author' => [
            'exclude' => true,
            'label' => 'core.db.pages:author',
            'config' => [
                'type' => 'input',
                'size' => 23,
                'eval' => 'trim',
                'max' => 255,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'author_email' => [
            'exclude' => true,
            'label' => 'core.db.pages:author_email',
            'config' => [
                'type' => 'email',
                'size' => 23,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'media' => [
            'exclude' => true,
            'label' => 'core.db.pages:media',
            'config' => [
                'type' => 'file',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'is_siteroot' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:is_siteroot',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'mount_pid_ol' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:mount_pid_ol',
            'config' => [
                'type' => 'radio',
                'items' => [
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.mount_pid_ol.I.0',
                        'value' => 0,
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.mount_pid_ol.I.1',
                        'value' => 1,
                    ],
                ],
            ],
        ],
        'module' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:module',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => '',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.module.I.4',
                        'value' => 'fe_users',
                        'icon' => 'status-user-frontend',
                    ],
                ],
                'default' => '',
            ],
        ],
        'l18n_cfg' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:l18n_cfg',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.l18n_cfg.I.1'],
                    ['label' => $GLOBALS['TYPO3_CONF_VARS']['FE']['hidePagesIfNotTranslatedByDefault'] ? 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.l18n_cfg.I.2a' : 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.l18n_cfg.I.2'],
                ],
            ],
        ],
        'backend_layout' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:backend_layout',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => ''],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.backend_layout.none', 'value' => -1],
                ],
                'itemsProcFunc' => \TYPO3\CMS\Backend\View\BackendLayoutView::class . '->addBackendLayoutItems',
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
                'fieldInformation' => [
                    'backendLayoutFromParentPage' => [
                        'renderType' => 'backendLayoutFromParentPage',
                    ],
                ],
                'dbFieldLength' => 64,
            ],
        ],
        'backend_layout_next_level' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:backend_layout_next_level',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => ''],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.backend_layout.none', 'value' => -1],
                ],
                'itemsProcFunc' => \TYPO3\CMS\Backend\View\BackendLayoutView::class . '->addBackendLayoutItems',
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
                'dbFieldLength' => 64,
            ],
        ],
        'tsconfig_includes' => [
            'l10n_mode' => 'exclude',
            'label' => 'core.db.pages:tsconfig_includes',
            'displayCond' => 'HIDE_FOR_NON_ADMINS',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'items' => [],
                'softref' => 'ext_fileref',
            ],
        ],
    ],
    'types' => [
        // normal
        (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    --palette--;;standard,
                    --palette--;;title,
                --div--;core.form.tabs:metadata,
                    --palette--;;abstract,
                    --palette--;;metatags,
                    --palette--;;editorial,
                --div--;core.form.tabs:appearance,
                    --palette--;;layout,
                    --palette--;;replace,
                --div--;core.form.tabs:behaviour,
                    --palette--;;links,
                    --palette--;;caching,
                    --palette--;;miscellaneous,
                    --palette--;;module,
                --div--;core.form.tabs:resources,
                    --palette--;;media,
                    --palette--;;config,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    --palette--;;visibility,
                    --palette--;;access,
                --div--;core.form.tabs:categories,
                    categories,
                --div--;core.form.tabs:notes,
                    rowDescription,
                --div--;core.form.tabs:extended,
            ',
        ],
        (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_BE_USER_SECTION => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    --palette--;;standard,
                    --palette--;;title,
                --div--;core.form.tabs:metadata,
                    --palette--;;abstract,
                    --palette--;;metatags,
                    --palette--;;editorial,
                --div--;core.form.tabs:appearance,
                    --palette--;;layout,
                    --palette--;;replace,
                --div--;core.form.tabs:behaviour,
                    --palette--;;links,
                    --palette--;;caching,
                    --palette--;;miscellaneous,
                    --palette--;;module,
                --div--;core.form.tabs:resources,
                    --palette--;;media,
                    --palette--;;config,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    --palette--;;visibility,
                    --palette--;;access,
                --div--;core.form.tabs:categories,
                    categories,
                --div--;core.form.tabs:notes,
                    rowDescription,
                --div--;core.form.tabs:extended,
            ',
        ],
        // external URL
        (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_LINK => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    doktype,
                    --palette--;;title,
                    --palette--;;external,
                --div--;core.form.tabs:metadata,
                    --palette--;;abstract,
                    --palette--;;editorial,
                --div--;core.form.tabs:appearance,
                    --palette--;;layout,
                --div--;core.form.tabs:behaviour,
                    --palette--;;miscellaneous,
                --div--;core.form.tabs:resources,
                    --palette--;;media,
                    --palette--;;config,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    --palette--;;visibility,
                    --palette--;;access,
                --div--;core.form.tabs:categories,
                    categories,
                --div--;core.form.tabs:notes,
                    rowDescription,
                --div--;core.form.tabs:extended,
            ',
        ],
        // shortcut
        (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_SHORTCUT => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    doktype,
                    --palette--;;title,
                    --palette--;;shortcut,
                    --palette--;;shortcutpage,
                --div--;core.form.tabs:metadata,
                    --palette--;;abstract,
                    --palette--;;editorial,
                --div--;core.form.tabs:appearance,
                    --palette--;;layout,
                --div--;core.form.tabs:behaviour,
                    --palette--;;links,
                    --palette--;;miscellaneous,
                --div--;core.form.tabs:resources,
                    --palette--;;media,
                    --palette--;;config,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    --palette--;;visibility,
                    --palette--;;access,
                --div--;core.form.tabs:categories,
                    categories,
                --div--;core.form.tabs:notes,
                    rowDescription,
                --div--;core.form.tabs:extended,
            ',
        ],
        // mount page
        (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_MOUNTPOINT => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    doktype,
                    --palette--;;title,
                    --palette--;;mountpoint,
                    --palette--;;mountpage,
                --div--;core.form.tabs:metadata,
                    --palette--;;abstract,
                    --palette--;;editorial,
                --div--;core.form.tabs:appearance,
                    --palette--;;layout,
                --div--;core.form.tabs:behaviour,
                    --palette--;;links,
                    --palette--;;miscellaneous,
                --div--;core.form.tabs:resources,
                    --palette--;;media,
                    --palette--;;config,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    --palette--;;visibility,
                    --palette--;;access,
                --div--;core.form.tabs:categories,
                    categories,
                --div--;core.form.tabs:notes,
                    rowDescription,
                --div--;core.form.tabs:extended,
            ',
        ],
        // spacer
        (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_SPACER => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    --palette--;;standard,
                    --palette--;;titleonly,
                --div--;core.form.tabs:appearance,
                    --palette--;;backend_layout,
                --div--;core.form.tabs:resources,
                    --palette--;;config,
                --div--;core.form.tabs:access,
                    --palette--;;visibility,
                    --palette--;;access,
                --div--;core.form.tabs:categories,
                    categories,
                --div--;core.form.tabs:notes,
                    rowDescription,
                --div--;core.form.tabs:extended,
            ',
        ],
        // Folder
        (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_SYSFOLDER => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    --palette--;;standard,
                    --palette--;;titleonly,
                --div--;core.form.tabs:appearance,
                    --palette--;;backend_layout,
                --div--;core.form.tabs:behaviour,
                    --palette--;;module,
                --div--;core.form.tabs:resources,
                    --palette--;;media,
                    --palette--;;config,
                --div--;core.form.tabs:access,
                    --palette--;;hiddenonly,
                    --palette--;;adminsonly,
                --div--;core.form.tabs:categories,
                    categories,
                --div--;core.form.tabs:notes,
                    rowDescription,
                --div--;core.form.tabs:extended,
            ',
        ],
    ],
    'palettes' => [
        'standard' => [
            'label' => 'core.form.palettes:standard',
            'showitem' => 'doktype',
        ],
        'shortcut' => [
            'showitem' => 'shortcut_mode',
        ],
        'shortcutpage' => [
            'showitem' => 'shortcut',
        ],
        'mountpoint' => [
            'showitem' => 'mount_pid_ol',
        ],
        'mountpage' => [
            'showitem' => 'mount_pid',
        ],
        'external' => [
            'showitem' => 'url, target',
        ],
        'title' => [
            'label' => 'core.form.palettes:title',
            'showitem' => 'title, --linebreak--, slug, --linebreak--, nav_title, --linebreak--, subtitle',
        ],
        'titleonly' => [
            'label' => 'core.form.palettes:title',
            'showitem' => 'title, --linebreak--, slug',
        ],
        'visibility' => [
            'label' => 'core.form.palettes:visibility',
            'showitem' => 'hidden;core.db.pages:hidden, nav_hide',
        ],
        'hiddenonly' => [
            'label' => 'core.form.palettes:visibility',
            'showitem' => 'hidden;core.db.pages:hidden',
        ],
        'access' => [
            'label' => 'core.form.palettes:access',
            'showitem' => 'starttime, endtime, extendToSubpages, --linebreak--, fe_group, --linebreak--, editlock',
        ],
        'abstract' => [
            'label' => 'core.form.palettes:abstract',
            'showitem' => 'abstract',
        ],
        'metatags' => [
            'label' => 'core.form.palettes:metatags',
            'showitem' => 'keywords',
        ],
        'editorial' => [
            'label' => 'core.form.palettes:editorial',
            'showitem' => 'author, author_email, lastUpdated',
        ],
        'layout' => [
            'label' => 'core.form.palettes:layout',
            'showitem' => 'layout, newUntil, --linebreak--, backend_layout, backend_layout_next_level',
        ],
        'backend_layout' => [
            'label' => 'core.form.palettes:page_layout',
            'showitem' => 'backend_layout, backend_layout_next_level',
        ],
        'module' => [
            'label' => 'core.form.palettes:use_as_container',
            'showitem' => 'module',
        ],
        'replace' => [
            'label' => 'core.form.palettes:replace',
            'showitem' => 'content_from_pid',
        ],
        'links' => [
            'label' => 'core.form.palettes:links_page',
            'showitem' => 'target;core.db.pages:link.target',
        ],
        'caching' => [
            'label' => 'core.form.palettes:caching',
            'showitem' => 'cache_timeout, cache_tags',
        ],
        'language' => [
            'label' => 'core.form.palettes:language',
            'showitem' => 'l18n_cfg',
        ],
        'miscellaneous' => [
            'label' => 'core.form.palettes:miscellaneous',
            'showitem' => 'is_siteroot, no_search, php_tree_stop',
        ],
        'adminsonly' => [
            'label' => 'core.form.palettes:miscellaneous',
            'showitem' => 'editlock',
        ],
        'media' => [
            'label' => 'core.form.palettes:media',
            'showitem' => 'media',
        ],
        'config' => [
            'label' => 'core.form.palettes:config',
            'showitem' => 'tsconfig_includes, --linebreak--, TSconfig',
        ],
    ],
];
