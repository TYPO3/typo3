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
