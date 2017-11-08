<?php
return [
    'ctrl' => [
        'title' => 'Form engine - inline expand child inline_1',
        'label' => 'uid',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
    ],


    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label'  => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0]
                ]
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_inline_expand_inline_1_child',
                'foreign_table_where' => 'AND tx_styleguide_inline_expand_inline_1_child.pid=###CURRENT_PID### AND tx_styleguide_inline_expand_inline_1_child.sys_language_uid IN (-1,0)',
            ]
        ],
        'l10n_source' => [
            'exclude' => true,
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'tx_styleguide_inline_expand_inline_1_child',
                'foreign_table_where' => 'AND tx_styleguide_inline_expand_inline_1_child.pid=###CURRENT_PID### AND tx_styleguide_inline_expand_inline_1_child.uid!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'hidden' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ],
        ],


        'parentid' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],


        'dummy_1' => [
            'exclude' => 1,
            'label' => 'dummy 1',
            'config' => [
                'type' => 'input',
            ],
        ],


        'inline_fal_1' => [
            'label' => 'inline_fal_1',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'sys_file_reference',
                'foreign_field' => "uid_foreign",
                'foreign_sortby' => "sorting_foreign",
                'foreign_table_field' => "tablenames",
                'foreign_match_fields' => [
                    'fieldname' => "image",
                ],
                'foreign_label' => "uid_local",
                'foreign_selector' => "uid_local",
                'filter' => [
                    'userFunc' => 'TYPO3\\CMS\\Core\\Resource\\Filter\\FileExtensionFilter->filterInlineChildren',
                    'parameters' => [
                        'allowedFileExtensions' => 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai',
                        'disallowedFileExtensions' => '',
                    ],
                ],
                'appearance' => [
                    'useSortable' => true,
                    'headerThumbnail' => [
                        'field' => "uid_local",
                        'width' => "45",
                        'height' => "45c",
                    ],
                    'showPossibleLocalizationRecords' => false,
                    'showRemovedLocalizationRecords' => false,
                    'showSynchronizationLink' => false,
                    'showAllLocalizationLink' => false,
                    'enabledControls' => [
                        'info' => true,
                        'new' => false,
                        'dragdrop' => true,
                        'sort' => false,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                    'createNewRelationLinkTitle' => "LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference",
                ],
                'overrideChildTca' => [
                    'columns' => [
                        'uid_local' => [
                            'config' => [
                                'appearance' => [
                                    'elementBrowserType' => 'file',
                                    'elementBrowserAllowed' => 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai',
                                ],
                            ],
                        ],
                    ],
                    'types' => [
                        0 => [
                            'showitem' => "--palette--;;imageoverlayPalette,--palette--;;filePalette",
                        ],
                        1 => [
                            'showitem' => "--palette--;;imageoverlayPalette,--palette--;;filePalette",
                        ],
                        2 => [
                            'showitem' => "--palette--;;imageoverlayPalette,--palette--;;filePalette",
                        ],
                        3 => [
                            'showitem' => "--palette--;;imageoverlayPalette,--palette--;;filePalette",
                        ],
                        4 => [
                            'showitem' => "--palette--;;imageoverlayPalette,--palette--;;filePalette",
                        ],
                        5 => [
                            'showitem' => "--palette--;;imageoverlayPalette,--palette--;;filePalette",
                        ],
                    ],
                ],
            ],
        ],


        'rte_1' => [
            'exclude' => 1,
            'label' => 'rte_1',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],


        'select_tree_1' => [
            'exclude' => 1,
            'label' => 'select_tree_1',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'size' => 8,
                'treeConfig' => [
                    'expandAll' => true,
                    'parentField' => 'pid',
                    'appearance' => [
                        'showHeader' => true,
                    ],
                ],
            ],
        ],


        't3editor_1' => [
            'exclude' => 1,
            'label' => 't3editor_1',
            'config' => [
                'type' => 'text',
                'renderType' => 't3editor',
            ],
        ],


    ],


    'types' => [
        '0' => [
            'showitem' => '
                --div--;fields,
                    inline_fal_1, rte_1, select_tree_1, t3editor_1,
                --div--;dummy,
                    dummy_1,
            ',
        ],


    ],


];
