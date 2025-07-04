<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_mm/Resources/Private/Language/locallang_db.xlf:tx_test_mm',
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
                    title, parent, group_mm_1_local, select_mm_1_local,
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
            'label' => 'Title',
            'l10n_mode' => 'prefixLangTitle',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'parent' => [
            'label' => 'Parent',
            'description' => 'one to many relation to tx_test_mm',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_test_mm',
                'foreign_table_where' => 'AND {#tx_test_mm}.{#sys_language_uid} IN (-1, 0) AND {#tx_test_mm}.{#uid} != ###THIS_UID###',
                'items' => [
                    [
                        'label' => 'Select',
                        'value' => 0,
                    ],
                ],
                'default' => 0,
            ],
        ],
        'group_mm_1_local' => [
            'label' => 'Group MM 1 - Local side (oppositeUsage)',
            'description' => 'MM relation featuring tablenames und fieldname in MM table',
            'config' => [
                'type' => 'group',
                'allowed' => '*',
                'MM' => 'group_mm_1_relations_mm',
                'MM_oppositeUsage' => [
                    'tt_content' => [
                        'group_mm_1_foreign',
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
        'select_mm_1_local' => [
            'label' => 'Select MM 1 - Local side',
            'description' => 'MM relation with only uid_local / uid_foreign in MM table',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tt_content',
                'foreign_table_where' => 'AND {#tt_content}.{#sys_language_uid} IN (-1,0)',
                'MM' => 'select_mm_1_relations_mm',
            ],
        ],
    ],
];
