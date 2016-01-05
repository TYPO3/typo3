<?php
return Array(
    'ctrl' => Array(
        'title' => 'Form engine tests - mn symmetric relation hotel',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => Array(
            'disabled' => 'hidden',
        ),
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'dividers2tabs' => true,
    ),
    'columns' => Array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
                )
            )
        ),
        'l18n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_styleguide_mnsymmetric_hotel',
                'foreign_table_where' => 'AND tx_styleguide_mnsymmetric_hotel.pid=###CURRENT_PID### AND tx_styleguide_mnsymmetric_hotel.sys_language_uid IN (-1,0)',
            )
        ),
        'l18n_diffsource' => array(
            'config' => array(
                'type' => 'passthrough'
            )
        ),
        'hidden' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => Array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'title' => Array(
            'exclude' => 1,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'Title',
            'config' => Array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'required',
            )
        ),
        'branches' => Array(
            'exclude' => 1,
            'label' => 'Branches',
            'config' => Array(
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_mnsymmetric_hotel_rel',
                'foreign_field' => 'hotelid',
                'foreign_sortby' => 'hotelsort',
                'foreign_label' => 'branchid',
                'symmetric_field' => 'branchid',
                'symmetric_sortby' => 'branchsort',
                'symmetric_label' => 'hotelid',
                'maxitems' => 10,
                'appearance' => array(
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showRemovedLocalizationRecords' => 1,
                ),
                'behaviour' => array(
                    'localizationMode' => 'select',
                ),
            )
        ),
    ),
    'interface' => Array(
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,title,branches'
    ),
    'types' => Array(
        '0' => Array(
            'showitem' => '--div--;General, title, branches,
                --div--;Visibility, sys_language_uid, l18n_parent,
                l18n_diffsource, hidden'
        )
    ),
);
