<?php

return [
    'ctrl' => [
        'title' => 'collection_recursive',
        'label' => 'fieldA',
        'hideTable' => true,
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'editlock' => 'editlock',
        'delete' => 'deleted',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'versioningWS' => true,
        'sortby' => 'sorting',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'transOrigPointerField' => 'l10n_parent',
        'translationSource' => 'l10n_source',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'typeicon_classes' => [
            'default' => 'typo3tests_contentelementb_collection_recursive-1-116cf86',
        ],
    ],
    'palettes' => [
        'language' => [
            'showitem' => 'sys_language_uid,l10n_parent',
        ],
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
        'fieldA' => [
            'label' => 'fieldA',
            'exclude' => true,
            'config' => [
                'type' => 'input',
            ],
        ],
        'collection_inner' => [
            'label' => 'collection_inner',
            'exclude' => true,
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'collection_inner',
                'foreign_field' => 'foreign_table_parent_uid',
            ],
        ],
    ],
    'types' => [
        1 => [
            'showitem' => '--div--;core.form.tabs:general,fieldA,collection_inner,--div--;core.form.tabs:language,--palette--;;language,--div--;core.form.tabs:access,--palette--;;hidden,--palette--;;access',
        ],
    ],
];
