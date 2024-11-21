<?php

return [
    'ctrl' => [
        'title' => 'DataHandler Testing test_refindex_softref',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:test_refindex_softref/Resources/Public/Icons/Extension.svg',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'translationSource' => 'l10n_source',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'text_1' => [
            'label' => 'text_1',
            'config' => [
                'type' => 'text',
                'softref' => 'email[subst]',
            ],
        ],
        'flex_1' => [
            'label' => 'flex_1',
            'config' => [
                'type' => 'flex',
                'ds' => '
                    <T3DataStructure>
                        <sheets>
                            <sheet_1>
                                <ROOT>
                                    <el>
                                        <text_1>
                                            <label>text_1</label>
                                            <config>
                                                <type>text</type>
                                                <softref>email[subst]</softref>
                                            </config>
                                        </text_1>
                                        <email_1>
                                            <label>email_1</label>
                                            <config>
                                                <type>email</type>
                                            </config>
                                        </email_1>
                                        <link_1>
                                            <label>link_1</label>
                                            <config>
                                                <type>link</type>
                                            </config>
                                        </link_1>
                                    </el>
                                </ROOT>
                            </sheet_1>
                            <section_1>
                                <ROOT>
                                    <sheetTitle>section_1</sheetTitle>
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
                                                            <label>text_1</label>
                                                            <config>
                                                                <type>text</type>
                                                                <softref>email[subst]</softref>
                                                            </config>
                                                        </input_1>
                                                        <email_1>
                                                            <label>email_1</label>
                                                            <config>
                                                                <type>email</type>
                                                            </config>
                                                        </email_1>
                                                        <link_1>
                                                            <label>link_1</label>
                                                            <config>
                                                                <type>link</type>
                                                            </config>
                                                        </link_1>
                                                    </el>
                                                </container_1>
                                                <container_2>
                                                    <type>array</type>
                                                    <title>container_2</title>
                                                    <el>
                                                        <input_1>
                                                            <label>text_2</label>
                                                            <config>
                                                                <type>text</type>
                                                                <softref>email[subst]</softref>
                                                            </config>
                                                        </input_1>
                                                    </el>
                                                </container_2>
                                            </el>
                                        </section_1>
                                    </el>
                                </ROOT>
                            </section_1>
                        </sheets>
                    </T3DataStructure>
                ',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;test,
                    text_1, flex_1,
                --div--;meta,
                    sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];
