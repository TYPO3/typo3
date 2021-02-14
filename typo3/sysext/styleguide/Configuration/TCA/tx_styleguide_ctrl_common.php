<?php

return [
    'ctrl' => [
        'title' => 'Form engine - Common table control',
        'label' => 'title_field',
        'descriptionColumn' => 'description_field',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'default_sortby' => 'title_field',
        'versioningWS' => true,
        'rootLevel' => -1,
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'searchFields' => 'title_field,description_field',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime'
        ],
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime'],
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
    ],
   'columns' => [

       'sys_language_uid' => [
           'exclude' => true,
           'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
           'config' => [
               'type' => 'select',
               'renderType' => 'selectSingle',
               'special' => 'languages',
               'items' => [
                   [
                       'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
                       -1,
                       'flags-multiple'
                   ],
               ],
               'default' => 0,
           ]
       ],
       'l10n_parent' => [
           'displayCond' => 'FIELD:sys_language_uid:>:0',
           'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
           'config' => [
               'type' => 'select',
               'renderType' => 'selectSingle',
               'items' => [
                   ['', 0]
               ],
               'foreign_table' => 'sys_category',
               'foreign_table_where' => 'AND sys_category.pid=###CURRENT_PID### AND sys_category.sys_language_uid IN (-1,0)',
               'default' => 0
           ]
       ],
       'l10n_diffsource' => [
           'config' => [
               'type' => 'passthrough',
               'default' => ''
           ]
       ],
       'hidden' => [
           'exclude' => true,
           'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
           'config' => [
               'type' => 'check',
               'renderType' => 'checkboxToggle',
               'items' => [
                   [
                       0 => '',
                       1 => '',
                       'invertStateDisplay' => true
                   ]
               ],
           ]
       ],
       'starttime' => [
           'exclude' => true,
           'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
           'config' => [
               'type' => 'input',
               'renderType' => 'inputDateTime',
               'eval' => 'datetime,int',
               'default' => 0,
               'behaviour' => [
                   'allowLanguageSynchronization' => true,
               ]
           ]
       ],
       'endtime' => [
           'exclude' => true,
           'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
           'config' => [
               'type' => 'input',
               'renderType' => 'inputDateTime',
               'eval' => 'datetime,int',
               'default' => 0,
               'range' => [
                   'upper' => mktime(0, 0, 0, 1, 1, 2038),
               ],
               'behaviour' => [
                   'allowLanguageSynchronization' => true,
               ]
           ]
       ],
       'title_field' => [
           'label' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:minimalTableTitleField',
           'config' => [
               'type' => 'input',
               'width' => 200,
               'eval' => 'trim,required'
           ],
       ],
       'description_field' => [
           'label' => 'description_field',
           'config' => [
               'type' => 'text',
           ],
       ],
   ],
   'types' => [
      '0' => [
         'showitem' => '
            title_field, description_field,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                --palette--;;language,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                hidden,--palette--;;timeRestriction,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
         ',
      ],
   ],
];
