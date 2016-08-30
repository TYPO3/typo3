<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'type' => 'type',
        'hideTable' => true,
        'rootLevel' => true,
        'default_sortby' => 'ORDER BY name ASC',
        'typeicon_column' => 'type',
        'typeicon_classes' => [
            '1' => 'mimetypes-text-text',
            '2' => 'mimetypes-media-image',
            '3' => 'mimetypes-media-audio',
            '4' => 'mimetypes-media-video',
            '5' => 'mimetypes-application',
            'default' => 'mimetypes-other-other'
        ],
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'searchFields' => 'name, type, mime_type, sha1'
    ],
    'interface' => [
        'showRecordFieldList' => 'storage, name, type, mime_type, size, sha1, missing'
    ],
    'columns' => [
        'fileinfo' => [
            'config' => [
                'type' => 'user',
                'userFunc' => 'TYPO3\\CMS\\Core\\Resource\\Hook\\FileInfoHook->renderFileInfo'
            ]
        ],
        'storage' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.storage',
            'config' => [
                'readOnly' => 1,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0]
                ],
                'foreign_table' => 'sys_file_storage',
                'foreign_table_where' => 'ORDER BY sys_file_storage.name',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1
            ]
        ],
        'identifier' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.identifier',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'name' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.name',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => '30',
                'eval' => 'required',
            ]
        ],
        'type' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.type',
            'config' => [
                'readOnly' => 1,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'size' => '1',
                'items' => [
                    ['LLL:EXT:lang/locallang_tca.xlf:sys_file.type.unknown', 0],
                    ['LLL:EXT:lang/locallang_tca.xlf:sys_file.type.text', 1],
                    ['LLL:EXT:lang/locallang_tca.xlf:sys_file.type.image', 2],
                    ['LLL:EXT:lang/locallang_tca.xlf:sys_file.type.audio', 3],
                    ['LLL:EXT:lang/locallang_tca.xlf:sys_file.type.video', 4],
                    ['LLL:EXT:lang/locallang_tca.xlf:sys_file.type.software', 5]
                ]
            ]
        ],
        'mime_type' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.mime_type',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'sha1' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.sha1',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => '30',
            ]
        ],
        'size' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.size',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => '8',
                'max' => '30',
                'eval' => 'int',
                'default' => 0
            ]
        ],
        'missing' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.missing',
            'config' => [
                'readOnly' => 1,
                'type' => 'check',
                'default' => 0
            ]
        ],
        'metadata' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.metadata',
            'config' => [
                'readOnly' => 1,
                'type' => 'inline',
                'foreign_table' => 'sys_file_metadata',
                'foreign_field' => 'file',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ]
        ]
    ],
    'types' => [
        '1' => ['showitem' => 'fileinfo, storage, missing']
    ],
    'palettes' => []
];
