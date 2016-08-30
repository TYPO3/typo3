<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_metadata',
        'label' => 'file',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'type' => 'file:type',
        'hideTable' => true,
        'rootLevel' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'default_sortby' => 'ORDER BY crdate DESC',
        'typeicon_classes' => [
            'default' => 'mimetypes-other-other'
        ],
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'searchFields' => 'file,title,description,alternative'
    ],
    'interface' => [
        'showRecordFieldList' => 'file, title, description, alternative'
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0]
                ],
                'default' => 0,
                'showIconTable' => true,
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0]
                ],
                'foreign_table' => 'sys_file_metadata',
                'foreign_table_where' => 'AND sys_file_metadata.uid=###REC_FIELD_l10n_parent### AND sys_file_metadata.sys_language_uid IN (-1,0)',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'exclude' => 0,
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        't3ver_label' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '30'
            ]
        ],
        'fileinfo' => [
            'config' => [
                'type' => 'user',
                'userFunc' => 'TYPO3\\CMS\\Core\\Resource\\Hook\\FileInfoHook->renderFileMetadataInfo'
            ]
        ],
        'file' => [
            'displayCond' => 'FIELD:sys_language_uid:=:0',
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file',
            'config' => [
                'readOnly' => 1,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_file',
                'minitems' => 1,
                'maxitems' => 1,
                'size' => 1,
                'default' => 0,
            ]
        ],
        'title' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.title',
            'l10n_mode' => 'prefixLangTitle',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'placeholder' => '__row|file|name'
            ]
        ],
        'description' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.description',
            'l10n_mode' => 'prefixLangTitle',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '3'
            ]
        ],
        'alternative' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.alternative',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ]
        ],
        'width' => [
            'exclude' => 0,
            'l10n_mode' => 'exclude'
        ],
        'height' => [
            'exclude' => 0,
            'l10n_mode' => 'exclude'
        ]
    ],
    'types' => [
        '1' => ['showitem' => 'fileinfo, title, description, alternative']
    ],
    'palettes' => []
];
