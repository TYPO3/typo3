<?php
return [
    'ctrl' => [
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'sortby' => 'sorting',
        'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_filemounts',
        'adminOnly' => 1,
        'rootLevel' => 1,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_filemounts'
        ],
        'useColumnsForDefaultValues' => 'path,base',
        'versioningWS_alwaysAllowLiveEdit' => true,
        'searchFields' => 'title,path'
    ],
    'interface' => [
        'showRecordFieldList' => 'title,hidden,path,base,description'
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_filemounts.title',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '30',
                'eval' => 'required,trim'
            ]
        ],
        'hidden' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
            'config' => [
                'type' => 'check'
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
                'max' => '2000',
            ]
        ],
        'base' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.baseStorage',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_file_storage',
                'size' => 1,
                'maxitems' => 1
            ]
        ],
        'path' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.folder',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [],
                'itemsProcFunc' => 'TYPO3\\CMS\\Core\\Resource\\Service\\UserFileMountService->renderTceformsSelectDropdown',
            ]
        ],
        'read_only' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_filemounts.read_only',
            'config' => [
                'type' => 'check'
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '--palette--;;mount, description, base, path, read_only']
    ],
    'palettes' => [
        'mount' => [
            'showitem' => 'title,hidden',
        ],
    ],
];
