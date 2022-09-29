<?php

return [
    'ctrl' => [
        'title' => 'Form engine - displayCond',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
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
            'config' => [
                'type' => 'check',
                'items' => [
                    ['Disable'],
                ],
            ],
        ],
        'sys_language_uid' => [
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
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_required',
                'foreign_table_where' => 'AND {#tx_styleguide_required}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_required}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0,
                    ],
                ],
                'foreign_table' => 'tx_styleguide_required',
                'foreign_table_where' => 'AND {#tx_styleguide_required}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_required}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],

        // Tab FIELD REQ start
        'select_1' => [
            'label' => 'select_1',
            'description' => 'Displays input_1 (true values) or input_2 (false values)',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'size' => 1,
                'maxitems' => 1,
                'items' => [
                    ['false values', '--div--'],
                    ['integer 0', 0],
                    ['string "0"', '0'],
                    ['bool false', false],
                    ['string empty', ''],
                    ['true values', '--div--'],
                    ['integer 1', 1],
                    ['bool true', true],
                    ['string "1"', '1'],
                    ['string "true"', 'true'],
                    ['string "false"', 'false'],
                ],
            ],
        ],
        'input_1' => [
            'label' => 'input_1',
            'description' => 'displayCond=FIELD:select_1:REQ:true',
            'displayCond' => 'FIELD:select_1:REQ:true',
            'config' => [
                'type' => 'input',
            ],
        ],
        'input_2' => [
            'label' => 'input_2',
            'description' => 'displayCond=FIELD:select_1:REQ:false',
            'displayCond' => 'FIELD:select_1:REQ:false',
            'config' => [
                'type' => 'input',
            ],
        ],
        // Tab FIELD REQ end

        // Tab FIELD compare start
        'number_1' => [
            'label' => 'number_1',
            'description' => 'Try values between 0 and 6',
            'onChange' => 'reload',
            'config' => [
                'type' => 'number',
            ],
        ],
        'input_4' => [
            'label' => 'input_4',
            'description' => 'displayCond=FIELD:number_1:=:0',
            'displayCond' => 'FIELD:number_1:=:0',
            'config' => [
                'type' => 'input',
            ],
        ],
        'input_5' => [
            'label' => 'input_5',
            'description' => 'displayCond=FIELD:number_1:<:5',
            'displayCond' => 'FIELD:number_1:<:5',
            'config' => [
                'type' => 'input',
            ],
        ],
        'input_6' => [
            'label' => 'input_6',
            'description' => 'displayCond=FIELD:number_1:>=:5',
            'displayCond' => 'FIELD:number_1:>=:5',
            'config' => [
                'type' => 'input',
            ],
        ],
        'input_7' => [
            'label' => 'input_7',
            'description' => 'displayCond=FIELD:number_1:-:2-4',
            'displayCond' => 'FIELD:number_1:-:2-4',
            'config' => [
                'type' => 'input',
            ],
        ],
        'input_8' => [
            'label' => 'input_8',
            'description' => 'displayCond=FIELD:number_1:IN:1,3,5',
            'displayCond' => 'FIELD:number_1:IN:1,3,5',
            'config' => [
                'type' => 'input',
            ],
        ],
        'input_9' => [
            'label' => 'input_9',
            'description' => 'displayCond=FIELD:number_1:!IN:1,3,5',
            'displayCond' => 'FIELD:number_1:!IN:1,3,5',
            'config' => [
                'type' => 'input',
            ],
        ],
        // Tab FIELD compare end

        // Tab FIELD AND OR start
        'select_2' => [
            'label' => 'select_2',
            'description' => 'To display input_19 choose foo1, for foo1 or foo42',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['foo1', 1],
                    ['foo2', 2],
                    ['foo42', 42],
                ],
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'checkbox_1' => [
            'label' => 'checkbox_1',
            'onChange' => 'reload',
            'description' => 'To display input_19 choose one checkbox, for input_20 neither',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo'],
                    ['bar'],
                ],
            ],
        ],
        'input_19' => [
            'label' => 'input_19:',
            'description' => 'displayCond=FIELD:select_2:=:1 AND checkbox_1:BIT:1',
            'displayCond' => [
                'AND' => [
                    'FIELD:select_2:=:1',
                    'FIELD:checkbox_1:BIT:1',
                ],
            ],
            'config' => [
                'type' => 'input',
            ],
        ],
        'input_20' => [
            'label' => 'input_20',
            'description' => 'FIELD:checkbox_1:=:0 AND (FIELD:select_2:=:1 OR FIELD:select_2:>:3)',
            'displayCond' => [
                'AND' => [
                    'FIELD:checkbox_1:=:0',
                    'OR' => [
                        'FIELD:select_2:=:1',
                        'FIELD:select_2:>:3',
                    ],
                ],
            ],
            'config' => [
                'type' => 'input',
            ],
        ],
        // Tab FIELD AND OR end

        // Tab REC:NEW start
        'input_10' => [
            'label' => 'input_10',
            'description' => 'displayCond=REC:NEW:true',
            'displayCond' => 'REC:NEW:true',
            'config' => [
                'type' => 'input',
            ],
        ],
        'input_11' => [
            'label' => 'input_11',
            'description' => 'displayCond=REC:NEW:false',
            'displayCond' => 'REC:NEW:false',
            'config' => [
                'type' => 'input',
            ],
        ],
        // Tab REC:NEW end

        // Tab HIDE_FOR_NON_ADMINS start
        'input_13' => [
            'label' => 'input_13',
            'description' => 'displayCond=HIDE_FOR_NON_ADMINS',
            'displayCond' => 'HIDE_FOR_NON_ADMINS',
            'config' => [
                'type' => 'input',
            ],
        ],
        // Tab HIDE_FOR_NON_ADMINS end

        // Tab USER start
        'number_2' => [
            'label' => 'number_2',
            'description' => 'Smaller value',
            'config' => [
                'type' => 'number',
            ],
        ],
        'number_3' => [
            'label' => 'number_3',
            'description' => 'Larger value',
            'config' => [
                'type' => 'number',
            ],
        ],
        'input_16' => [
            'label' => 'input_16',
            'description' => 'displayCond=USER:TYPO3\CMS\Styleguide\UserFunctions\FormEngine\DisplayConditionUserFunc->lessThen:number_2:number_3',
            'displayCond' => 'USER:TYPO3\CMS\Styleguide\UserFunctions\FormEngine\DisplayConditionUserFunc->lessThen:number_2:number_3',
            'config' => [
                'type' => 'input',
            ],
        ],
        // Tab USER end

        // Tab VERSION:IS start
        'input_17' => [
            'label' => 'input_17',
            'description' => 'displayCond=VERSION:IS:true',
            'displayCond' => 'VERSION:IS:true',
            'config' => [
                'type' => 'input',
            ],
        ],
        'input_18' => [
            'label' => 'input_18',
            'description' => 'displayCond=VERSION:IS:false',
            'displayCond' => 'VERSION:IS:false',
            'config' => [
                'type' => 'input',
            ],
        ],
        // Tab VERSION:IS end

        // Tab Flexforms start
        'select_3' => [
            'label' => 'select_3',
            'description' => 'Show or hide a field in a flexform',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Hide input_2 on flex_1', 0],
                    ['Show input_2 on flex_1', 1],
                ],
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'flex_1' => [
            'label' => 'flex_1',
            'description' => 'Diplay conditions within a Flexform',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <ROOT>
                                <type>array</type>
                                <el>
                                    <check_1>
                                        <label>check_1</label>
                                        <description>display input_1 and select_tree_3 on flex_1, hide input_1 on flex_2 </description>
                                        <onChange>reload</onChange>
                                        <config>
                                            <type>check</type>
                                        </config>
                                    </check_1>
                                    <input_1>
                                        <label>input_1</label>
                                        <description>FIELD:check_1:REQ:TRUE</description>
                                        <displayCond>FIELD:check_1:REQ:TRUE</displayCond>
                                        <config>
                                            <type>input</type>
                                        </config>
                                    </input_1>
                                    <input_2>
                                        <label>input_2</label>
                                        <description>FIELD:parentRec.select_3:=:1</description>
                                        <displayCond>FIELD:parentRec.select_3:=:1</displayCond>
                                        <config>
                                            <type>input</type>
                                        </config>
                                    </input_2>
                                    <select_tree_1>
                                        <label>select_tree_1</label>
                                        <description>FIELD:check_1:REQ:TRUE</description>
                                        <displayCond>FIELD:check_1:REQ:TRUE</displayCond>
                                        <config>
                                            <type>select</type>
                                            <renderType>selectTree</renderType>
                                            <foreign_table>pages</foreign_table>
                                            <size>20</size>
                                            <maxitems>4</maxitems>
                                            <treeConfig>
                                                <expandAll>1</expandAll>
                                                <parentField>pid</parentField>
                                                <appearance>
                                                    <showHeader>1</showHeader>
                                                </appearance>
                                            </treeConfig>
                                        </config>
                                    </select_tree_1>
                                </el>
                            </ROOT>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],
        'flex_2' => [
            'label' => 'flex_2',
            'description' => 'Diplay conditions within a Flexform',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure><sheets>
                            <sheet_1>
                                <ROOT>
                                    <sheetTitle>sheet_1</sheetTitle>
                                    <type>array</type>
                                    <el>
                                        <select_1>
                                            <label>select_1</label>
                                            <onChange>reload</onChange>
                                            <config>
                                                <type>select</type>
                                                <renderType>selectSingle</renderType>
                                                <items type="array">
                                                    <numIndex index="0" type="array">
                                                        <numIndex index="0">hide input_2</numIndex>
                                                        <numIndex index="1">0</numIndex>
                                                    </numIndex>
                                                    <numIndex index="1" type="array">
                                                        <numIndex index="0">show input_2</numIndex>
                                                        <numIndex index="1">1</numIndex>
                                                    </numIndex>
                                                </items>
                                                <maxitems>1</maxitems>
                                                <size>1</size>
                                            </config>
                                        </select_1>
                                        <!-- todo: This one gets never displayed -->
                                        <input_1>
                                            <label>input_1</label>
                                            <description>FIELD:parentRec.flex_1.check_1:=:0</description>
                                            <displayCond>FIELD:parentRec.flex_1.check_1:=:0</displayCond>
                                            <config>
                                                <type>input</type>
                                            </config>
                                        </input_1>
                                        <input_2>
                                            <label>input_2</label>
                                            <description>FIELD:select_1:=:1</description>
                                            <displayCond>FIELD:select_1:=:1</displayCond>
                                            <config>
                                                <type>input</type>
                                            </config>
                                        </input_2>
                                        <input_3>
                                            <label>input_3</label>
                                            <description>FIELD:sheet_2.select_1:=:1</description>
                                            <displayCond>FIELD:sheet_2.select_1:=:1</displayCond>
                                            <config>
                                                <type>input</type>
                                            </config>
                                        </input_3>
                                        <input_4>
                                            <label>input_4</label>
                                            <description>FIELD:sheet_2.select_1:=:1 AND FIELD:parentRec.select_3:=:1</description>
                                            <displayCond>
                                                <and>
                                                    <value1>FIELD:sheet_2.select_1:=:1</value1>
                                                    <value2>FIELD:parentRec.select_3:=:1</value2>
                                                </and>
                                            </displayCond>
                                            <config>
                                                <type>input</type>
                                            </config>
                                        </input_4>
                                    </el>
                                </ROOT>
                            </sheet_1>
                            <sheet_2>
                                <ROOT>
                                    <sheetTitle>sheet_2</sheetTitle>
                                    <type>array</type>
                                    <el>
                                        <select_1>
                                            <label>select_1</label>
                                            <onChange>reload</onChange>
                                            <config>
                                                <type>select</type>
                                                <renderType>selectSingle</renderType>
                                                <items type="array">
                                                    <numIndex index="0" type="array">
                                                        <numIndex index="0">hide input_3 on sheet_1</numIndex>
                                                        <numIndex index="1">0</numIndex>
                                                    </numIndex>
                                                    <numIndex index="1" type="array">
                                                        <numIndex index="0">show input_3 on sheet_1</numIndex>
                                                        <numIndex index="1">1</numIndex>
                                                    </numIndex>
                                                </items>
                                                <maxitems>1</maxitems>
                                                <size>1</size>
                                            </config>
                                        </select_1>
                                    </el>
                                </ROOT>
                            </sheet_2>
                        </sheets>
                    </T3DataStructure>
                    ',
                ],
            ],
        ],

        'select_4' => [
            'label' => 'select_4',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 1,
                'items' => [
                    0 => [
                        'hide input_2 in flex_3',
                        0,
                    ],
                    1 => [
                        'show input_2 in flex_3',
                        1,
                    ],
                ],
            ],
        ],
        'flex_3' => [
            'label' => 'flex3',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sheet_1>
                                    <ROOT>
                                        <sheetTitle>sheet_1</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <select_1>
                                                <label>select_1</label>
                                                <onChange>reload</onChange>
                                                <config>
                                                    <type>select</type>
                                                    <renderType>selectSingle</renderType>
                                                    <items type="array">
                                                        <numIndex index="0" type="array">
                                                            <numIndex index="0">input_3 and input_4 not shown</numIndex>
                                                            <numIndex index="1">0</numIndex>
                                                        </numIndex>
                                                        <numIndex index="1" type="array">
                                                            <numIndex index="0">input_3 and input_4 shown</numIndex>
                                                            <numIndex index="1">1</numIndex>
                                                        </numIndex>
                                                    </items>
                                                    <maxitems>1</maxitems>
                                                    <size>1</size>
                                                </config>
                                            </select_1>
                                            <section_1>
                                                <title>section_1</title>
                                                <type>array</type>
                                                <section>1</section>
                                                <el>
                                                    <container_1>
                                                        <type>array</type>
                                                        <title>container_1</title>
                                                        <el>
                                                             <select_2>
                                                                <label>select_2</label>
                                                                <onChange>reload</onChange>
                                                                <config>
                                                                    <type>select</type>
                                                                    <renderType>selectSingle</renderType>
                                                                    <items type="array">
                                                                        <numIndex index="0" type="array">
                                                                            <numIndex index="0">input_5 not shown</numIndex>
                                                                            <numIndex index="1">0</numIndex>
                                                                        </numIndex>
                                                                        <numIndex index="1" type="array">
                                                                            <numIndex index="0">input_5 shown</numIndex>
                                                                            <numIndex index="1">1</numIndex>
                                                                        </numIndex>
                                                                    </items>
                                                                    <maxitems>1</maxitems>
                                                                    <size>1</size>
                                                                </config>
                                                            </select_2>
                                                            <input_1>
                                                                <label>input_1</label>
                                                                <description>Always shown</description>
                                                                <config>
                                                                    <type>input</type>
                                                                </config>
                                                            </input_1>
                                                            <input_2>
                                                                <label>input_2</label>
                                                                <description>FIELD:parentRec.select_4:=:1</description>
                                                                <displayCond>FIELD:parentRec.select_4:=:1</displayCond>
                                                                <config>
                                                                    <type>input</type>
                                                                </config>
                                                            </input_2>
                                                            <input_3>
                                                                <label>input_3</label>
                                                                <description>FIELD:select_1:=:1</description>
                                                                <displayCond>FIELD:select_1:=:1</displayCond>
                                                                <config>
                                                                    <type>input</type>
                                                                </config>
                                                            </input_3>
                                                            <input_4>
                                                                <label>input_4</label>
                                                                <description>FIELD:sheet_1.select_1:=:1</description>
                                                                <displayCond>FIELD:sheet_1.select_1:=:1</displayCond>
                                                                <config>
                                                                    <type>input</type>
                                                                </config>
                                                            </input_4>
                                                            <input_5>
                                                                <label>input_5</label>
                                                                <description>FIELD:select_2:=:1</description>
                                                                <displayCond>FIELD:select_2:=:1</displayCond>
                                                                <config>
                                                                    <type>input</type>
                                                                </config>
                                                            </input_5>
                                                            <input_6>
                                                                <label>input_6</label>
                                                                <description>FIELD:sheet_2.select_1:=:1</description>
                                                                <displayCond>FIELD:sheet_2.select_1:=:1</displayCond>
                                                                <config>
                                                                    <type>input</type>
                                                                </config>
                                                            </input_6>
                                                        </el>
                                                    </container_1>
                                                </el>
                                            </section_1>
                                        </el>
                                    </ROOT>
                                </sheet_1>
                                <sheet_2>
                                    <ROOT>
                                        <sheetTitle>sheet_2</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <select_1>
                                                <label>select_1</label>
                                                <onChange>reload</onChange>
                                                <config>
                                                    <type>select</type>
                                                    <renderType>selectSingle</renderType>
                                                    <items type="array">
                                                        <numIndex index="0" type="array">
                                                            <numIndex index="0">input_6 on sheet_1 containers not shown</numIndex>
                                                            <numIndex index="1">0</numIndex>
                                                        </numIndex>
                                                        <numIndex index="1" type="array">
                                                            <numIndex index="0">input_6 on sheet_1 containers shown</numIndex>
                                                            <numIndex index="1">1</numIndex>
                                                        </numIndex>
                                                    </items>
                                                    <maxitems>1</maxitems>
                                                    <size>1</size>
                                                </config>
                                            </select_1>
                                        </el>
                                    </ROOT>
                                </sheet_2>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],
        // Tab Flexforms end
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;FIELD REQ,
                    select_1,
                    input_1,
                    input_2,
                --div--;FIELD compare,
                    number_1,
                    input_4,
                    input_5,
                    input_6,
                    input_7,
                    input_8,
                    input_9,
                --div--;FIELD AND OR,
                    select_2,
                    checkbox_1,
                    input_19,
                    input_20,
                --div--;REC:NEW,
                    input_10,
                    input_11,
                --div--;HIDE_FOR_NON_ADMINS,
                    input_13,
                --div--;USER,
                    number_2,
                    number_3,
                    input_16,
                --div--;VERSION:IS,
                    input_17,
                    input_18,
                --div--;Flexforms,
                    select_3,
                    flex_1,
                    flex_2,
                    select_4,
                    flex_3,
            ',
        ],
    ],

];
