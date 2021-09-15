<?php

return [
    'ctrl' => [
        'title' => 'DataHandler Testing test_select_flex_mm local',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:test_select_flex_mm/Resources/Public/Icons/Extension.gif',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'translationSource' => 'l10n_source',
    ],

    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language'
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
                'foreign_table' => 'tx_testselectflexmm_local',
                'foreign_table_where' => 'AND {#tx_testselectflexmm_local}.{#pid}=###CURRENT_PID### AND {#tx_testselectflexmm_local}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_testselectflexmm_local',
                'foreign_table_where' => 'AND {#tx_testselectflexmm_local}.{#pid}=###CURRENT_PID### AND {#tx_testselectflexmm_local}.{#uid}!=###THIS_UID###',
                'default' => 0
            ]
        ],

        'flex_1' => [
            'label' => 'flex_1',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sMultiplesidebyside>
                                    <ROOT>
                                        <type>array</type>
                                        <TCEforms>
                                            <sheetTitle>selectMultipleSideBySide</sheetTitle>
                                        </TCEforms>
                                        <el>
                                            <select_multiplesidebyside_1>
                                                <TCEforms>
                                                    <label>select_multiplesidebyside_1</label>
                                                    <config>
                                                        <type>select</type>
                                                        <renderType>selectMultipleSideBySide</renderType>
                                                        <foreign_table>tx_testselectflexmm_foreign</foreign_table>
                                                        <MM>tx_testselectflexmm_flex_1_multiplesidebyside_1_mm</MM>
                                                        <size>5</size>
                                                        <autoSizeMax>5</autoSizeMax>
                                                    </config>
                                                </TCEforms>
                                            </select_multiplesidebyside_1>
                                        </el>
                                    </ROOT>
                                </sMultiplesidebyside>
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
