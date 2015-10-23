<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym',
        'label' => 'term',
        'default_sortby' => 'ORDER BY term',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime'
        ),
        'typeicon_classes' => array(
            'default' => 'mimetypes-x-tx_rtehtmlarea_acronym'
        )
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden,sys_language_uid,term,acronym'
    ),
    'columns' => array(
        'hidden' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'starttime' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => array(
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
            )
        ),
        'endtime' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => array(
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
                'range' => array(
                    'upper' => mktime(0, 0, 0, 12, 31, 2020),
                    'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
                )
            )
        ),
        'sys_language_uid' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', '-1'),
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', '0')
                ),
                'default' => 0,
                'showIconTable' => true,
            )
        ),
        'type' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.type',
            'config' => array(
                'type' => 'radio',
                'items' => array(
                    array('LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.type.I.1', '2'),
                    array('LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.type.I.0', '1')
                ),
                'default' => '2'
            )
        ),
        'term' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.term',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim,required'
            )
        ),
        'acronym' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.acronym',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim,required'
            )
        ),
    ),
    'types' => array(
        '0' => array(
            'showitem' => 'hidden, --palette--;;1, sys_language_uid, type, term, acronym',
        ),
    ),
    'palettes' => array(
        '1' => array(
            'showitem' => 'starttime, endtime',
        ),
    ),
);
