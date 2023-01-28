<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tx_testirreforeignfield_price',
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
        'iconfile' => 'EXT:test_irre_foreignfield/Resources/Public/Icons/icon_price.gif',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
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
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_testirreforeignfield_price',
                'foreign_table_where' => 'AND {#tx_testirreforeignfield_price}.{#pid}=###CURRENT_PID### AND {#tx_testirreforeignfield_price}.{#sys_language_uid} IN (-1,0)',
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
            'label' => 'LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tx_testirreforeignfield_price.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'price' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tx_testirreforeignfield_price.price',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 30,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tabs.general, title, price,' .
            '--div--;LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden, parentid',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
