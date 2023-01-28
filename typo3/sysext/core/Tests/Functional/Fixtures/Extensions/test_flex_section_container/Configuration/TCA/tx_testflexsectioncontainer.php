<?php

return [
    'ctrl' => [
        'title' => 'DataHandler Testing test_flex_section_container',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:test_flex_section_container/Resources/Public/Icons/Extension.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'translationSource' => 'l10n_source',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tx_testflexsectioncontainer',
                'foreign_table_where' => 'AND {#tx_testflexsectioncontainer}.{#pid}=###CURRENT_PID### AND {#tx_testflexsectioncontainer}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
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
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tx_testflexsectioncontainer',
                'foreign_table_where' => 'AND {#tx_testflexsectioncontainer}.{#pid}=###CURRENT_PID### AND {#tx_testflexsectioncontainer}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],

        'flex_1' => [
            'label' => 'flex_1',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sSection>
                                    <ROOT>
                                        <sheetTitle>section</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <section_1>
                                                <title>section_1</title>
                                                <type>array</type>
                                                <section>1</section>
                                                <el>
                                                    <container_1>
                                                        <type>array</type>
                                                        <title>container_1</title>
                                                        <el>
                                                            <input_1>
                                                                <label>input_1 description</label>
                                                                <description>field description</description>
                                                                <config>
                                                                    <type>input</type>
                                                                </config>
                                                            </input_1>
                                                        </el>
                                                    </container_1>
                                                </el>
                                            </section_1>
                                        </el>
                                    </ROOT>
                                </sSection>
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
                --div--;flex,
                    flex_1,
                --div--;meta,
                    sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];
