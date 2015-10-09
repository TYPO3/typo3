<?php
return array(
    'ctrl' => array(
        'label' => 'subject',
        'default_sortby' => 'ORDER BY crdate',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser',
        'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
        'delete' => 'deleted',
        'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note',
        'typeicon_classes' => array(
            'default' => 'mimetypes-x-sys_note'
        ),
        'sortby' => 'sorting'
    ),
    'interface' => array(
        'showRecordFieldList' => 'category,subject,message,personal'
    ),
    'columns' => array(
        'category' => array(
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.category',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('', '0', 'sysnote-type-0'),
                    array('LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.1', '1', 'sysnote-type-1'),
                    array('LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.3', '3', 'sysnote-type-3'),
                    array('LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.4', '4', 'sysnote-type-4'),
                    array('LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.2', '2', 'sysnote-type-2')
                ),
                'default' => '0',
                'showIconTable' => true,
            )
        ),
        'subject' => array(
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.subject',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'max' => '255'
            )
        ),
        'message' => array(
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.message',
            'config' => array(
                'type' => 'text',
                'cols' => '40',
                'rows' => '15'
            )
        ),
        'personal' => array(
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.personal',
            'config' => array(
                'type' => 'check'
            )
        )
    ),
    'types' => array(
        '0' => array('showitem' => 'category, personal, subject, message')
    )
);
