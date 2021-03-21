<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline fal',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
    ],

    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'Disable',
                    ],
                ],
            ],
        ],
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
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'tx_styleguide_inline_fal',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_fal}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_fal}.{#sys_language_uid} IN (-1,0)',
                'default' => 0
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
                'foreign_table' => 'tx_styleguide_inline_fal',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_fal}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_fal}.{#uid}!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],

        'inline_1' => [
            'exclude' => 1,
            'label' => 'inline_1 typical fal image',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'inline_1',
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                    ],
                    'overrideChildTca' => [
                        'columns' => [
                            'crop' => [
                                'description' => 'field description',
                            ],
                        ],
                        'types' => [
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                                'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette'
                            ],
                        ],
                    ],
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            ),
        ],
        'inline_2' => [
            'exclude' => 1,
            'label' => 'inline_2 read only fal images',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'inline_2',
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                    ],
                    'overrideChildTca' => [
                        'columns' => [
                            'crop' => [
                                'description' => 'field description',
                            ],
                        ],
                        'types' => [
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                                'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette'
                            ],
                        ],
                    ],
                    'readOnly' => true,
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            ),
        ],
        'inline_3' => [
            'label' => 'inline_3 media fal',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'inline_3',
                [],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']
            ),
        ],
        'inline_4' => [
            'label' => 'inline_4 media fal with allowLanguageSynchronization',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'inline_4',
                [
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']
            ),
        ],
        'inline_5' => [
            'label' => 'inline_5 appearance localization toggles',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'inline_5',
                [
                    'appearance' => [
                        'showPossibleLocalizationRecords' => true,
                        'showRemovedLocalizationRecords' => true,
                        'showAllLocalizationLink' => true,
                        'showSynchronizationLink' => true,
                    ]
                ],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']
            ),
        ],


        'inline_flex_1' => [
            'exclude' => 1,
            'label' => 'flex_1',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                        <sheets>
                            <sInline>
                                <ROOT>
                                    <TCEforms>
                                        <sheetTitle>Inline</sheetTitle>
                                    </TCEforms>
                                    <type>array</type>
                                    <el>
                                        <fal>
                                            <TCEforms>
                                                <label>inline_flex_1</label>
                                                <config>
                                                    <type>inline</type>
                                                    <foreign_table>sys_file_reference</foreign_table>
                                                    <foreign_field>uid_foreign</foreign_field>
                                                    <foreign_sortby>sorting_foreign</foreign_sortby>
                                                    <foreign_table_field>tablenames</foreign_table_field>
                                                    <foreign_match_fields>
                                                        <fieldname>fal</fieldname>
                                                    </foreign_match_fields>
                                                    <foreign_label>uid_local</foreign_label>
                                                    <foreign_selector>uid_local</foreign_selector>
                                                    <filter>
                                                        <userFunc>TYPO3\\CMS\\Core\\Resource\\Filter\\FileExtensionFilter->filterInlineChildren</userFunc>
                                                        <parameters type="array">
                                                            <allowedFileExtensions>gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai</allowedFileExtensions>
                                                            <disallowedFileExtensions>ai</disallowedFileExtensions>
                                                        </parameters>
                                                    </filter>
                                                    <appearance>
                                                        <useSortable>1</useSortable>
                                                        <headerThumbnail>
                                                            <field>uid_local</field>
                                                            <width>45</width>
                                                            <height>45c</height>
                                                        </headerThumbnail>
                                                        <showPossibleLocalizationRecords>0</showPossibleLocalizationRecords>
                                                        <showRemovedLocalizationRecords>0</showRemovedLocalizationRecords>
                                                        <showSynchronizationLink>0</showSynchronizationLink>
                                                        <showAllLocalizationLink>0</showAllLocalizationLink>
                                                        <enabledControls>
                                                            <info>1</info>
                                                            <new>0</new>
                                                            <dragdrop>1</dragdrop>
                                                            <sort>0</sort>
                                                            <hide>1</hide>
                                                            <delete>1</delete>
                                                            <localize>1</localize>
                                                        </enabledControls>
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
                                                        <columns type="array">
                                                            <uid_local type="array">
                                                                <config type="array">
                                                                    <appearance type="array">
                                                                        <elementBrowserType>file</elementBrowserType>
                                                                        <elementBrowserAllowed>gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai</elementBrowserAllowed>
                                                                    </appearance>
                                                                </config>
                                                            </uid_local>
                                                        </columns>
                                                    </overrideChildTca>
                                                </config>
                                            </TCEforms>
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
                    inline_1, inline_2,
                --div--;media,
                    inline_3, inline_4, inline_5,
                --div--;in flex,
                    inline_flex_1,
            ',
        ],
    ],

];
