<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'file_collections' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:file_collections',
            'config' => [
                'type' => 'group',
                'localizeReferencesAtParentLocalization' => true,
                'allowed' => 'sys_file_collection',
                'foreign_table' => 'sys_file_collection',
                'size' => 5,
                'fieldControl' => [
                    'addRecord' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:file_mountpoints_add_title',
                            'setValue' => 'prepend',
                        ],
                    ],
                ],
            ],
        ],
        'filelink_size' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_size',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'filelink_sorting' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.none', 'value' => ''],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.extension', 'value' => 'extension'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.name', 'value' => 'name'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.type', 'value' => 'type'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.size', 'value' => 'size'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.creation_date', 'value' => 'creation_date'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.modification_date', 'value' => 'modification_date'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.title', 'value' => 'title'],
                ],
                'dbFieldLength' => 64,
            ],
        ],
        'filelink_sorting_direction' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting_direction',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => ''],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting_direction.ascending', 'value' => 'asc'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting_direction.descending', 'value' => 'desc'],
                ],
                'dbFieldLength' => 4,
            ],
        ],
        'target' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:target',
            'config' => [
                'type' => 'input',
                'max' => 30,
                'size' => 20,
                'eval' => 'trim',
                'valuePicker' => [
                    'items' => [
                        [ 'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:target.I.1', 'value' => '_blank' ],
                    ],
                ],
                'default' => '',
            ],
        ],
        'uploads_description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:uploads_description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'uploads_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:uploads_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:uploads_type.0', 'value' => 0],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:uploads_type.1', 'value' => 1],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:uploads_type.2', 'value' => 2],
                ],
                'default' => 0,
            ],
        ],
    ]
);

$GLOBALS['TCA']['tt_content']['palettes']['uploads'] = [
    'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media',
    'showitem' => '
        media;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.ALT.uploads_formlabel,
        --linebreak--,
        file_collections;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:file_collections.ALT.uploads_formlabel,
        --linebreak--,
        filelink_sorting,
        filelink_sorting_direction,
        target
    ',
];
$GLOBALS['TCA']['tt_content']['palettes']['uploadslayout'] = [
    'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.uploads_layout',
    'showitem' => '
        filelink_size;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_size_formlabel,
        uploads_description,
        uploads_type
    ',
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.uploads',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.uploads.description',
        'value' => 'uploads',
        'icon' => 'mimetypes-x-content-list-files',
        'group' => 'lists',
    ],
    '
        --palette--;;headers,
        --palette--;;uploads,
        --palette--;;uploadslayout,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
        --palette--;;frames,
        --palette--;;appearanceLinks,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories'
);
