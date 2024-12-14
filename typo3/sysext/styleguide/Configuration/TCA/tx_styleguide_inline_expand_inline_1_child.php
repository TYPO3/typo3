<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline expand child inline_1',
        'label' => 'uid',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'parentid' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],

        'dummy_1' => [
            'label' => 'dummy 1',
            'config' => [
                'type' => 'input',
            ],
        ],

        'file_1' => [
            'label' => 'file_1',
            'config' => [
                'type' => 'file',
                'allowed' => 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai',
                'appearance' => [
                    'headerThumbnail' => [
                        'width' => '45',
                        'height' => '45c',
                    ],
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
            ],
        ],

        'rte_1' => [
            'label' => 'rte_1',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],

        'select_tree_1' => [
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
            'label' => 't3editor_1',
            'config' => [
                'type' => 'text',
                'renderType' => 'codeEditor',
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;fields,
                    file_1, rte_1, select_tree_1, t3editor_1,
                --div--;dummy,
                    dummy_1,
            ',
        ],

    ],

];
