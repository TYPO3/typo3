<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_categorized_content',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_categorized_content.description',
        'value' => 'menu_categorized_content',
        'icon' => 'content-menu-categorized',
        'group' => 'menu',
    ],
    '
        --palette--;;headers,
        selected_categories,
        category_field,
    --div--;core.form.tabs:appearance,
        --palette--;;frames,
        --palette--;;appearanceLinks,
    --div--;core.form.tabs:categories,
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
                        'table' => 'tt_content',
                    ],
                ],
            ],
        ],
    ]
);
