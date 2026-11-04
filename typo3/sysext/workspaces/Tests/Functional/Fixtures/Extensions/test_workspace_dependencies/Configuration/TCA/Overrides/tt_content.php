<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_testworkspacedependencies_children_list' => [
            'label' => 'Children (inline, comma separated list)',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testworkspacedependencies_child',
            ],
        ],
        'tx_testworkspacedependencies_children' => [
            'label' => 'Children (inline, foreign_field)',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testworkspacedependencies_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ],
        ],
        'tx_testworkspacedependencies_image' => [
            'label' => 'Single image',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'relationship' => 'manyToOne',
            ],
        ],
        'tx_testworkspacedependencies_flex' => [
            'label' => 'Flex with relations of several kinds',
            'config' => [
                'type' => 'flex',
                'ds' => '
                    <T3DataStructure>
                        <sheets>
                            <sDEF>
                                <ROOT>
                                    <type>array</type>
                                    <el>
                                        <related>
                                            <label>Related content (group)</label>
                                            <config>
                                                <type>group</type>
                                                <allowed>tt_content</allowed>
                                            </config>
                                        </related>
                                        <link>
                                            <label>Link (soft reference)</label>
                                            <config>
                                                <type>link</type>
                                            </config>
                                        </link>
                                        <text>
                                            <label>Rich text (soft reference)</label>
                                            <config>
                                                <type>text</type>
                                                <enableRichtext>1</enableRichtext>
                                                <softref>typolink_tag</softref>
                                            </config>
                                        </text>
                                        <children>
                                            <label>Children (inline)</label>
                                            <config>
                                                <type>inline</type>
                                                <foreign_table>tx_testworkspacedependencies_child</foreign_table>
                                            </config>
                                        </children>
                                    </el>
                                </ROOT>
                            </sDEF>
                        </sheets>
                    </T3DataStructure>
                ',
            ],
        ],
    ]
);
