<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_categorized_pages',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_categorized_pages.description',
        'value' => 'menu_categorized_pages',
        'icon' => 'content-menu-categorized',
        'group' => 'menu',
    ],
    '
        --palette--;;headers,
        selected_categories,
        category_field,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
        --palette--;;frames,
        --palette--;;appearanceLinks,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories',
    [
        'columnsOverrides' => [
            'selected_categories' => [
                'config' => [
                    'minitems' => 1,
                ],
            ],
            'category_field' => [
                'config' => [
                    'itemsProcConfig' => [
                        'table' => 'pages',
                    ],
                ],
            ],
        ],
    ]
);
