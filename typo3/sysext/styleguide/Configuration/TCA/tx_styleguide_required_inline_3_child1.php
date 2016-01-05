<?php
return array(
    'ctrl' => array(
        'title' => 'Form engine tests - Required fields - inline_3 child 1',
        'label' => 'input_1',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms_staticdata.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY crdate',
    ),
    'columns' => array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
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
                'foreign_table' => 'tx_styleguide_forms_inline_2_child2',
                'foreign_table_where' => 'AND tx_styleguide_forms_inline_2_child2.pid=###CURRENT_PID### AND tx_styleguide_forms_inline_2_child2.sys_language_uid IN (-1,0)',
            )
        ),
        'l18n_diffsource' => array(
            'config' => array(
                'type' => 'passthrough'
            )
        ),
        'hidden' => array(
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            ),
        ),
        'parentid' => array(
            'config' => array(
                'type' => 'passthrough',
            )
        ),
        'parenttable' => array(
            'config' => array(
                'type' => 'passthrough',
            )
        ),
        'input_1' => array(
            'label' => 'input_1',
            'config' => array(
                'type' => 'input',
                'eval' => 'required',
            ),
        ),
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, parentid, parenttable, input_1',
    ),
    'types' => array(
        '0' => array(
            'showitem' => 'input_1',
        ),
    ),
);
