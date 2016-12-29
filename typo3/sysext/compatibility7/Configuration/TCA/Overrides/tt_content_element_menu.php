<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    // Add the CType
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.12',
            'menu',
            'content-special-menu'
        ],
        'shortcut',
        'before'
    );
    $GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= ',menu_type';
    $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['menu'] = 'content-special-menu';
    $GLOBALS['TCA']['tt_content']['palettes']['menu'] = [
        'showitem' => '
            menu_type;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type_formlabel,
            --linebreak--,
            pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.menu_formlabel
        ',
    ];
    $GLOBALS['TCA']['tt_content']['types']['menu'] = [
        'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.menu;menu,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.menu_accessibility;menu_accessibility,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                --palette--;;language,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                categories,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                rowDescription,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        ',
        'subtype_value_field' => 'menu_type',
        'subtypes_excludelist' => [
            '2' => 'pages',
            'categorized_pages' => 'pages',
            'categorized_content' => 'pages',
        ],
        'subtypes_addlist' => [
            'categorized_pages' => 'selected_categories, category_field',
            'categorized_content' => 'selected_categories, category_field',
        ]
    ];

    // Add additional fields
    $additionalColumns = [
        'menu_type' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.0',
                        '0'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.1',
                        '1'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.2',
                        '4'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.3',
                        '7'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.4',
                        '2'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.8',
                        '8'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.5',
                        '3'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.6',
                        '5'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.7',
                        '6'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.categorized_pages',
                        'categorized_pages'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.categorized_content',
                        'categorized_content'
                    ]
                ],
                'default' => 0
            ]
        ]
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $additionalColumns);
});
