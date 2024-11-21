<?php

return [
    'ctrl' => [
        'title' => 'DataHandler Testing test_select_flex_mm local',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:test_select_flex_mm/Resources/Public/Icons/Extension.svg',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'translationSource' => 'l10n_source',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'flex_1' => [
            'label' => 'flex_1',
            'config' => [
                'type' => 'flex',
                'ds' => '
                    <T3DataStructure>
                        <sheets>
                            <sMultiplesidebyside>
                                <ROOT>
                                    <type>array</type>
                                    <sheetTitle>selectMultipleSideBySide</sheetTitle>
                                    <el>
                                        <select_multiplesidebyside_1>
                                            <label>select_multiplesidebyside_1</label>
                                            <config>
                                                <type>select</type>
                                                <renderType>selectMultipleSideBySide</renderType>
                                                <foreign_table>tx_testselectflexmm_foreign</foreign_table>
                                                <MM>tx_testselectflexmm_flex_1_multiplesidebyside_1_mm</MM>
                                                <size>5</size>
                                                <autoSizeMax>5</autoSizeMax>
                                            </config>
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
