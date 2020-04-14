<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Flexform;

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

class ActionTest extends AbstractDataHandlerActionTestCase
{
    const VALUE_ContentId = 100;
    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Flexform/DataSet/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');
    }

    /**
     * @test
     */
    public function transformationAppliesForRichTextFieldsWithoutSheets()
    {
        $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['default'] = '<?xml version="1.0" encoding="UTF-8"?>
<T3DataStructure>
    <meta>
        <langDisable>0</langDisable>
    </meta>
    <ROOT type="array">
        <type>array</type>
        <el type="array">
            <settings.bodytext>
                <TCEforms type="array">
                    <label>Random Bodytext</label>
                    <config type="array">
                        <type>text</type>
                        <cols>48</cols>
                        <rows>5</rows>
                        <enableRichtext>1</enableRichtext>
                        <richtextConfiguration>default</richtextConfiguration>
                    </config>
                </TCEforms>
            </settings.bodytext>
        </el>
    </ROOT>
</T3DataStructure>';

        $expected = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.isNotDefined">1</field>
                <field index="settings.bodytext">
                    <value index="vDEF">&lt;p class=&quot;align-right&quot;&gt;First line&lt;/p&gt;
&lt;p&gt;Last line&lt;/p&gt;</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $this->getActionService()->modifyRecords(1, [
            'tt_content' => [
                'uid' => self::VALUE_ContentId,
                'pi_flexform' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'settings.isNotDefined' => '1',
                                'settings.bodytext' => [
                                    'vDEF' => '<p class="align-right">First line</p>

<p>Last line</p>'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $flexFormContent = $queryBuilder
            ->select('pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(self::VALUE_ContentId, \PDO::PARAM_INT)))
            ->execute()
            ->fetchColumn(0);

        self::assertEquals($expected, $flexFormContent);
    }

    /**
     * @test
     */
    public function transformationAppliesForRichTextFieldsWithSheets()
    {
        $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['default'] = '<T3DataStructure>
    <meta>
        <langDisable>1</langDisable>
    </meta>
    <sheets>
        <sheet1>
            <ROOT>
                <TCEforms>
                    <sheetTitle>Text Example with an RTE field</sheetTitle>
                </TCEforms>
                <type>array</type>
                <el>
                    <settings.bodytext>
                        <label>Random Bodytext</label>
                        <config>
                            <type>text</type>
                            <rows>5</rows>
                            <cols>30</cols>
                            <eval>trim,required</eval>
                            <enableRichtext>1</enableRichtext>
                            <richtextConfiguration>default</richtextConfiguration>
                        </config>
                    </settings.bodytext>
                </el>
            </ROOT>
        </sheet1>
    </sheets>
</T3DataStructure>';

        $expected = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sheet1">
            <language index="lDEF">
                <field index="settings.isNotDefined">1</field>
                <field index="settings.bodytext">
                    <value index="vDEF">&lt;p class=&quot;align-right&quot;&gt;First line&lt;/p&gt;
&lt;p&gt;Last line&lt;/p&gt;</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $this->getActionService()->modifyRecords(1, [
            'tt_content' => [
                'uid' => self::VALUE_ContentId,
                'pi_flexform' => [
                    'data' => [
                        'sheet1' => [
                            'lDEF' => [
                                'settings.isNotDefined' => '1',
                                'settings.bodytext' => [
                                    'vDEF' => '<p class="align-right">First line</p>

<p>Last line</p>'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $flexFormContent = $queryBuilder
            ->select('pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(self::VALUE_ContentId, \PDO::PARAM_INT)))
            ->execute()
            ->fetchColumn(0);

        self::assertEquals($expected, $flexFormContent);
    }
}
