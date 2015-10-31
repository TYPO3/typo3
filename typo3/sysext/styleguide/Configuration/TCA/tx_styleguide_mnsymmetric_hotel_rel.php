<?php
return Array(
    'ctrl' => Array(
        'title' => 'Form engine tests - mn symmetric relation hotel relation',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
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
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_styleguide_mnsymmetric_hotel_rel',
                'foreign_table_where' => 'AND tx_styleguide_mnsymmetric_hotel_rel.pid=###CURRENT_PID###
                    AND tx_styleguide_mnsymmetric_hotel_rel.sys_language_uid IN (-1,0)',
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
        'hotelid' => Array(
            'label' => 'Hotelid',
            'config' => Array(
                'type' => 'select',
                'foreign_table' => 'tx_styleguide_mnsymmetric_hotel',
                'maxitems' => 1,
                'localizeReferences' => 1,
            )
        ),
        'branchid' => Array(
            'label' => 'Branchid',
            'config' => Array(
                'type' => 'select',
                'foreign_table' => 'tx_styleguide_mnsymmetric_hotel',
                'maxitems' => 1,
                'localizeReferences' => 1,
            )
        ),
        'hotelsort' => Array(
            'config' => Array(
                'type' => 'passthrough',
            )
        ),
        'branchsort' => Array(
            'config' => Array(
                'type' => 'passthrough',
            )
        ),
    ),
    'interface' => Array(
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,title,hotelid,offerid,hotelsort,offersort'
    ),
    'types' => Array(
        '0' => Array(
            'showitem' => '--div--;General, title, hotelid,
                branchid, --div--;Visibility, sys_language_uid,
                l18n_parent, l18n_diffsource, hidden, hotelsort, branchsort'
        )
    ),
);