<?php

return [
    'ctrl' => [
        'label' => 'languageId',
        'label_userFunc' => \TYPO3\CMS\Backend\Configuration\TCA\UserFunctions::class . '->getSiteLanguageTitle',
        'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.ctrl.title',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-domain',
        ],
    ],
    'columns' => [
        'languageId' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.languageId',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => \TYPO3\CMS\Backend\Configuration\TCA\ItemsProcessorFunctions::class . '->populateAvailableLanguagesFromSites',
            ],
        ],
        'title' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.title',
            'config' => [
                'type' => 'input',
                'size' => 15,
                'required' => true,
                'eval' => 'trim',
                'placeholder' => 'English',
            ],
        ],
        'navigationTitle' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.navigationTitle',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_language.navigationTitle',
            'config' => [
                'type' => 'input',
                'size' => 15,
                'eval' => 'trim',
                'placeholder' => 'English',
            ],
        ],
        'base' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.base',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_language.base',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'size' => 15,
                'default' => '/',
                'placeholder' => '/',
            ],
        ],
        'websiteTitle' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.websiteTitle',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_language.websiteTitle',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'locale' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.locale',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_language.locale',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'size' => 20,
                'placeholder' => 'en-US',
                'valuePicker' => [
                    'mode' => '',
                    'items' => \TYPO3\CMS\Backend\Configuration\TCA\UserFunctions::getAllSystemLocales(),
                ],
            ],
        ],
        'hreflang' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.hreflang',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_language.hreflang',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 6,
                'default' => '',
                'placeholder' => 'en-US',
            ],
        ],
        'enabled' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'flag' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.flag',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemGroups' => [
                    'misc' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.flag.group.misc',
                    'countries' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.flag.group.countries',
                    'colors' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.flag.group.colors',
                ],
                'itemsProcFunc' => \TYPO3\CMS\Backend\Configuration\TCA\ItemsProcessorFunctions::class . '->populateFlags',
                'items' => [
                    ['label' => 'default', 'value' => 'global', 'icon' => 'flags-multiple', 'group' => 'misc'],
                    ['label' => 'en-us-gb', 'value' => 'en-us-gb', 'icon' => 'flags-en-us-gb', 'group' => 'misc'],
                    ['label' => 'eu', 'value' => 'eu', 'icon' => 'flags-eu', 'group' => 'misc'],
                ],
                'sortItems' => [
                    'label' => 'asc',
                ],
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'fallbackType' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.fallbackType',
            'displayCond' => 'FIELD:languageId:>:0',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.fallbackType.strict', 'value' => 'strict'],
                    ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.fallbackType.fallback', 'value' => 'fallback'],
                    ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.fallbackType.free', 'value' => 'free'],
                ],
            ],
        ],
        'fallbacks' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.fallbacks',
            'displayCond' => 'FIELD:languageId:>:0',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'itemsProcFunc' => \TYPO3\CMS\Backend\Configuration\TCA\ItemsProcessorFunctions::class . '->populateFallbackLanguages',
                'size' => 5,
                'min' => 0,
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '--palette--;;default, --palette--;;rendering-related, flag, --palette--;;languageIdPalette',
        ],
    ],
    'palettes' => [
        'default' => [
            'showitem' => 'title, enabled, --linebreak--, locale, hreflang, --linebreak--, base',
        ],
        'languageIdPalette' => [
            'showitem' => 'languageId',
            'isHiddenPalette' => true,
        ],
        'rendering-related' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_language.palette.frontend',
            'showitem' => 'websiteTitle, navigationTitle, --linebreak--, fallbackType, --linebreak--, fallbacks',
        ],
    ],
];
