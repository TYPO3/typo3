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
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
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
        'starttime' => [
            'exclude' => 1,
            'label' => 'Publish Date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => '0'
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'Expiration Date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
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
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            ),
        ],
        'inline_2' => [
            'label' => 'inline_2 media fal',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'inline_2',
                [],
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
                                                    <foreign_selector_fieldTcaOverride>
                                                        <config>
                                                            <appearance>
                                                                <elementBrowserType>file</elementBrowserType>
                                                                <elementBrowserAllowed>gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai</elementBrowserAllowed>
                                                            </appearance>
                                                        </config>
                                                    </foreign_selector_fieldTcaOverride>
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
                                                    <behaviour type="array">
                                                        <localizationMode>select</localizationMode>
                                                        <localizeChildrenAtParentLocalization>1</localizeChildrenAtParentLocalization>
                                                    </behaviour>
                                                    <foreign_types type="array">
                                                        <numIndex index="0">
                                                            <showitem>
                                                                --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                        <numIndex index="1">
                                                            <showitem>
                                                                --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                        <numIndex index="2">
                                                            <showitem>
                                                                --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                        <numIndex index="3">
                                                            <showitem>
                                                                --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                        <numIndex index="4">
                                                            <showitem>
                                                                --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                        <numIndex index="5">
                                                            <showitem>
                                                                --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette
                                                            </showitem>
                                                        </numIndex>
                                                    </foreign_types>
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
                    inline_1,
                --div--;media,
                    inline_2,
                --div--;in flex,
                    inline_flex_1,
            ',
        ],
    ],


];
