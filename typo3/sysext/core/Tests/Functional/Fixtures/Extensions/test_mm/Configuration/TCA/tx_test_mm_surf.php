<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_mm/Resources/Private/Language/locallang_db.xlf:tx_test_mm_surf',
        'descriptionColumn' => 'description',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'default_sortby' => 'title',
        'versioningWS' => true,
        'rootLevel' => -1,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'searchFields' => 'title',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'typeicon_classes' => [
            'default' => 'actions-surfboard',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    title, base, relations, posts,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,--palette--;;timeRestriction,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime'],
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:test_mm/Resources/Private/Language/locallang_db.xlf:tx_test_mm_surf.title',
            'l10n_mode' => 'prefixLangTitle',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'base' => [
            'label' => 'LLL:EXT:test_mm/Resources/Private/Language/locallang_db.xlf:tx_test_mm_surf.base',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_test_mm_surf',
                'foreign_table_where' => 'AND {#tx_test_mm_surf}.{#sys_language_uid} IN (-1, 0) AND {#tx_test_mm_surf}.{#uid} != ###THIS_UID###',
                'items' => [
                    [
                        'label' => 'LLL:EXT:test_mm/Resources/Private/Language/locallang_db.xlf:tx_test_mm_surf.base.0',
                        'value' => 0,
                    ],
                ],
                'default' => 0,
            ],
        ],
        'relations' => [
            'label' => 'LLL:EXT:test_mm/Resources/Private/Language/locallang_db.xlf:tx_test_mm_surf.relations',
            'config' => [
                'type' => 'group',
                'allowed' => '*',
                'MM' => 'surf_content_mm',
                'MM_oppositeUsage' => [
                    'tt_content' => [
                        'surfing',
                    ],
                ],
                'size' => 10,
                'fieldWizard' => [
                    'recordsOverview' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'posts' => [
            'label' => 'Posts',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tt_content',
                'foreign_table_where' => 'AND {#tt_content}.{#sys_language_uid} IN (-1,0)',
                'MM' => 'surf_content_surfers_mm',
            ],
        ],
    ],
];
