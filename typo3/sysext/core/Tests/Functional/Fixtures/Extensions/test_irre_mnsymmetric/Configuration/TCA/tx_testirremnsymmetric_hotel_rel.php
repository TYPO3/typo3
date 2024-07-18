<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_irre_mnsymmetric/Resources/Private/Language/locallang_db.xlf:tx_testirremnsymmetric_hotel_rel',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'translationSource' => 'l10n_source',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:test_irre_mnsymmetric/Resources/Public/Icons/icon_hotel_rel.gif',
        'versioningWS' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'hotelid' => [
            'label' => 'LLL:EXT:test_irre_mnsymmetric/Resources/Private/Language/locallang_db.xlf:tx_testirremnsymmetric_hotel_rel.hotelid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_testirremnsymmetric_hotel',
                'default' => 0,
            ],
        ],
        'branchid' => [
            'label' => 'LLL:EXT:test_irre_mnsymmetric/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_rel.branchid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_testirremnsymmetric_hotel',
                'default' => 0,
            ],
        ],
        'hotelsort' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'branchsort' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;LLL:EXT:test_irre_mnsymmetric/Resources/Private/Language/locallang_db.xlf:tabs.general, title, hotelid, branchid,' .
            '--div--;LLL:EXT:test_irre_mnsymmetric/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden, hotelsort, branchsort',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
