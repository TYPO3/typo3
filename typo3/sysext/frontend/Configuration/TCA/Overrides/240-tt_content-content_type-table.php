<?php

defined('TYPO3') or die();

$contentType = 'table';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.table',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.table.description',
        'value' => $contentType,
        'icon' => 'content-table',
        'group' => 'lists',
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'cols' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:cols',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:cols.I.0', 'value' => '0'],
                    ['label' => '1', 'value' => '1'],
                    ['label' => '2', 'value' => '2'],
                    ['label' => '3', 'value' => '3'],
                    ['label' => '4', 'value' => '4'],
                    ['label' => '5', 'value' => '5'],
                    ['label' => '6', 'value' => '6'],
                    ['label' => '7', 'value' => '7'],
                    ['label' => '8', 'value' => '8'],
                    ['label' => '9', 'value' => '9'],
                ],
                'default' => 0,
            ],
        ],
        'table_class' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_class',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_class.default', 'value' => ''],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_class.striped', 'value' => 'striped'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_class.bordered', 'value' => 'bordered'],
                ],
                'default' => '',
                'dbFieldLength' => 60,
            ],
        ],
        'table_caption' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_caption',
            'config' => [
                'type' => 'input',
            ],
        ],
        'table_delimiter' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_delimiter',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_delimiter.124', 'value' => 124],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_delimiter.59', 'value' => 59],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_delimiter.44', 'value' => 44],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_delimiter.58', 'value' => 58],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_delimiter.9', 'value' => 9],
                ],
                'default' => 124,
            ],
        ],
        'table_enclosure' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_enclosure',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_enclosure.0', 'value' => 0],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_enclosure.39', 'value' => 39],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_enclosure.34', 'value' => 34],
                ],
                'default' => 0,
            ],
        ],
        'table_header_position' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_header_position',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_header_position.0', 'value' => 0],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_header_position.1', 'value' => 1],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_header_position.2', 'value' => 2],
                ],
                'default' => 0,
            ],
        ],
        'table_tfoot' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_tfoot',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
    ]
);

$GLOBALS['TCA']['tt_content'] = array_replace_recursive(
    $GLOBALS['TCA']['tt_content'],
    [
        'ctrl' => [
            'typeicon_classes' => [
                $contentType => 'mimetypes-x-content-table',
            ],
        ],
        'palettes' => [
            'tableconfiguration' => [
                'showitem' => 'table_delimiter,table_enclosure',
            ],
            'tablelayout' => [
                'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.table_layout',
                'showitem' => 'cols, table_class, table_header_position, table_tfoot',
            ],
        ],
        'types' => [
            $contentType => [
                'showitem' => '
                        --palette--;;headers,
                        bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.table.bodytext,
                        --palette--;;tableconfiguration,
                        table_caption,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                        --palette--;;frames,
                        --palette--;;tablelayout,
                        --palette--;;appearanceLinks,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                        categories,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                ',
                'columnsOverrides' => [
                    'bodytext' => [
                        'config' => [
                            'renderType' => 'textTable',
                            'wrap' => 'off',
                        ],
                    ],
                ],
            ],
        ],
    ]
);
