<?php

defined('TYPO3') or die();

$contentType = 'menu_categorized_content';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_categorized_content',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_categorized_content.description',
        'value' => $contentType,
        'icon' => 'content-menu-categorized',
        'group' => 'menu',
    ],
);

$GLOBALS['TCA']['tt_content'] = array_replace_recursive(
    $GLOBALS['TCA']['tt_content'],
    [
        'ctrl' => [
            'typeicon_classes' => [
                $contentType => 'content-menu-categorized',
            ],
        ],
        'types' => [
            $contentType => [
                'showitem' => '
                        --palette--;;headers,
                        selected_categories,
                        category_field,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                        --palette--;;frames,
                        --palette--;;appearanceLinks,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                        categories,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                ',
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
            ],
        ],
    ]
);
