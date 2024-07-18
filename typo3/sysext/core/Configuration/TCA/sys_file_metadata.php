<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata',
        'label' => 'file',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'type' => 'file:type',
        'hideTable' => true,
        'rootLevel' => 1,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'versioningWS' => true,
        'default_sortby' => 'crdate DESC',
        'typeicon_classes' => [
            'default' => 'mimetypes-other-other',
        ],
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'searchFields' => 'title,description,alternative',
    ],
    'columns' => [
        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'categories' => [
            'config' => [
                'type' => 'category',
            ],
        ],
        'fileinfo' => [
            'config' => [
                'type' => 'none',
                'renderType' => 'fileInfo',
            ],
        ],
        'file' => [
            'displayCond' => 'FIELD:sys_language_uid:=:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file',
            'config' => [
                'readOnly' => true,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_file',
                'foreign_table_where' => 'AND {#sys_file}.{#uid} = ###REC_FIELD_file###',
                'minitems' => 1,
                'default' => 0,
            ],
        ],
        'title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.title',
            'l10n_mode' => 'prefixLangTitle',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'placeholder' => '__row|file|name',
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.description',
            'l10n_mode' => 'prefixLangTitle',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'alternative' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.alternative',
            'description' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.alternative.description',
            'l10n_mode' => 'prefixLangTitle',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'width' => [
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:file.width',
            'config' => [
                'type' => 'number',
                'size' => 10,
                'default' => 0,
                'readOnly' => true,
            ],
        ],
        'height' => [
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:file.height',
            'config' => [
                'type' => 'number',
                'size' => 10,
                'default' => 0,
                'readOnly' => true,
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                fileinfo, alternative, description, title, --palette--;;language,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                categories,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        '],
    ],
    'palettes' => [
        'language' => [
            'showitem' => 'sys_language_uid, l10n_parent',
            'isHiddenPalette' => true,
        ],
    ],
];
