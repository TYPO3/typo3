<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tx_testirreforeignfield_hotel',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'translationSource' => 'l10n_source',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:test_irre_foreignfield/Resources/Public/Icons/icon_hotel.gif',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_testirreforeignfield_hotel',
                'foreign_table_where' => 'AND {#tx_testirreforeignfield_hotel}.{#pid}=###CURRENT_PID### AND {#tx_testirreforeignfield_hotel}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
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
        'parentidentifier' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tx_testirreforeignfield_hotel.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'offers' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tx_testirreforeignfield_hotel.offers',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testirreforeignfield_offer',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                ],
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tabs.general, title, offers,' .
            '--div--;LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
