<?php

return [
    'ctrl' => [
        'title' => 'Form engine - file',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
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
        'file_1' => [
            'label' => 'file_1 typical fal image',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
                'overrideChildTca' => [
                    'columns' => [
                        'crop' => [
                            'description' => 'field description',
                        ],
                    ],
                    'types' => [
                        \TYPO3\CMS\Core\Resource\FileType::IMAGE->value => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                    ],
                ],
            ],
        ],
        'file_2' => [
            'label' => 'file_2 read only fal images',
            'config' => [
                'type' => 'file',
                'readOnly' => true,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
                'overrideChildTca' => [
                    'columns' => [
                        'crop' => [
                            'description' => 'field description',
                        ],
                    ],
                    'types' => [
                        \TYPO3\CMS\Core\Resource\FileType::IMAGE->value => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                    ],
                ],
            ],
        ],
        'file_3' => [
            'label' => 'file_3 media fal',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-media-types',
            ],
        ],
        'file_4' => [
            'label' => 'file_4 media fal with allowLanguageSynchronization',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-media-types',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'file_5' => [
            'label' => 'file_5 appearance localization toggles',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-media-types',
                'appearance' => [
                    'showPossibleLocalizationRecords' => true,
                    'showAllLocalizationLink' => true,
                    'showSynchronizationLink' => true,
                ],
            ],
        ],
        'file_6' => [
            'label' => 'file_6 with RTE for image description',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-media-types',
                'overrideChildTca' => [
                    'columns' => [
                        'description' => [
                            'config' => [
                                'type' => 'text',
                                'enableRichtext' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],

        'file_flex_1' => [
            'label' => 'flex_1',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                        <sheets>
                            <sInline>
                                <ROOT>
                                    <sheetTitle>File</sheetTitle>
                                    <type>array</type>
                                    <el>
                                        <fal>
                                            <label>file_flex_1</label>
                                            <config>
                                                <type>file</type>
                                                <allowed>gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai</allowed>
                                                <appearance>
                                                    <headerThumbnail>
                                                        <width>45</width>
                                                        <height>45c</height>
                                                    </headerThumbnail>
                                                    <createNewRelationLinkTitle>LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference</createNewRelationLinkTitle>
                                                </appearance>
                                                <overrideChildTca>
                                                    <types type="array">
                                                        <numIndex index="0">
                                                            <showitem>
                                                                --palette--;;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                        <numIndex index="1">
                                                            <showitem>
                                                                --palette--;;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                        <numIndex index="2">
                                                            <showitem>
                                                                --palette--;;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                        <numIndex index="3">
                                                            <showitem>
                                                                --palette--;;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                        <numIndex index="4">
                                                            <showitem>
                                                                --palette--;;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                        <numIndex index="5">
                                                            <showitem>
                                                                --palette--;;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                    </types>
                                                </overrideChildTca>
                                            </config>
                                        </fal>
                                    </el>
                                </ROOT>
                            </sInline>
                        </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;typical fal,
                    file_1, file_2,
                --div--;media,
                    file_3, file_4, file_5, file_6,
                --div--;in flex,
                    file_flex_1,
            ',
        ],
    ],

];
