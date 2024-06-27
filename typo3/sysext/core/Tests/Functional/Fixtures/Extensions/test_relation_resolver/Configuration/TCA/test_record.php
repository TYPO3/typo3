<?php

return [
    'ctrl' => [
        'title' => 'typo3tests/test-record',
        'label' => 'title',
        'hideTable' => true,
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'origUid' => 't3_origuid',
        'editlock' => 'editlock',
        'delete' => 'deleted',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'versioningWS' => true,
        'sortby' => 'sorting',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'typeicon_classes' => [
            'default' => 'test_record-typo3tests_testrecord-cc2849f',
        ],
        'searchFields' => 'title',
    ],
    'palettes' => [
        'hidden' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.visibility',
            'showitem' => 'hidden',
        ],
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access',
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel,--linebreak--,fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel,--linebreak--,editlock',
        ],
    ],
    'columns' => [
        'foreign_table_parent_uid' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'tablenames' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'fieldname' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'label' => 'title',
            'exclude' => true,
            'config' => [
                'type' => 'input',
            ],
        ],
        'record_collection' => [
            'label' => 'record_collection',
            'exclude' => true,
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'record_collection',
                'foreign_field' => 'foreign_table_parent_uid',
            ],
        ],
    ],
    'types' => [
        1 => [
            'showitem' => '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,title,record_collection,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,--palette--;;hidden,--palette--;;access',
        ],
    ],
];
