<?php

return [
    'ctrl' => [
        'title' => 'Form engine - Common table control',
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'default_sortby' => 'title',
        'versioningWS' => true,
        'rootLevel' => -1,
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'searchFields' => 'title,description',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime'],
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
    ],
   'columns' => [

       'sys_language_uid' => [
           'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
           'config' => [
               'type' => 'language',
           ],
       ],
       'l10n_parent' => [
           'displayCond' => 'FIELD:sys_language_uid:>:0',
           'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
           'config' => [
               'type' => 'select',
               'renderType' => 'selectSingle',
               'items' => [
                   ['', 0],
               ],
               'foreign_table' => 'sys_category',
               'foreign_table_where' => 'AND sys_category.pid=###CURRENT_PID### AND sys_category.sys_language_uid IN (-1,0)',
               'default' => 0,
           ],
       ],
       'l10n_diffsource' => [
           'config' => [
               'type' => 'passthrough',
               'default' => '',
           ],
       ],
       'hidden' => [
           'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
           'config' => [
               'type' => 'check',
               'renderType' => 'checkboxToggle',
               'items' => [
                   [
                       0 => '',
                       'invertStateDisplay' => true,
                   ],
               ],
           ],
       ],
       'starttime' => [
           'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
           'config' => [
               'type' => 'datetime',
               'eval' => 'int',
               'default' => 0,
               'behaviour' => [
                   'allowLanguageSynchronization' => true,
               ],
           ],
       ],
       'endtime' => [
           'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
           'config' => [
               'type' => 'datetime',
               'eval' => 'int',
               'default' => 0,
               'range' => [
                   'upper' => mktime(0, 0, 0, 1, 1, 2038),
               ],
               'behaviour' => [
                   'allowLanguageSynchronization' => true,
               ],
           ],
       ],
       'title' => [
           'label' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:minimalTableTitleField',
           'config' => [
               'type' => 'input',
               'width' => 200,
               'eval' => 'trim',
               'required' => true,
           ],
       ],
       'description' => [
           'label' => 'description',
           'config' => [
               'type' => 'text',
           ],
       ],
   ],
   'types' => [
      '0' => [
         'showitem' => '
            title, description,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                --palette--;;language,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                hidden,--palette--;;timeRestriction,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
         ',
      ],
   ],
];
