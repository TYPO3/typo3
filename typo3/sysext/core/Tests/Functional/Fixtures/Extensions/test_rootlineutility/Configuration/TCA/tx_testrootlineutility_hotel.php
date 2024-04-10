<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Hotels',
        'label' => 'title',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'iconfile' => 'EXT:test_rootlineutility/Resources/Public/Icons/icon_hotel.gif',
        'versioningWS' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'sys_language_uid' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_testrootlineutility_hotel',
                'foreign_table_where' => 'AND {#tx_testrootlineutility_hotel}.{#pid}=###CURRENT_PID### AND {#tx_testrootlineutility_hotel}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'starttime' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2106),
                ],
            ],
        ],
        'parentid' => [
            'config' => [
                'type' => 'passthrough',
                'default' => 0,
            ],
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'title' => [
            'label' => 'Hotel title',
            'config' => [
                'type' => 'text',
                'rows' => 1,
                'required' => true,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;General, title,' .
            '--div--;Visibility, sys_language_uid, l10n_parent, hidden, starttime, endtime',
        ],
    ],
];
