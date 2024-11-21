<?php

declare(strict_types=1);

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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ActionTest extends FunctionalTestCase
{
    private const VALUE_ContentId = 100;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultElements.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function transformationAppliesForRichTextFieldsWithoutSheets(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'] = '<?xml version="1.0" encoding="UTF-8"?>
<T3DataStructure>
    <meta>
        <langDisable>0</langDisable>
    </meta>
    <ROOT type="array">
        <type>array</type>
        <el type="array">
            <settings.bodytext>
                <label>Random Bodytext</label>
                <config type="array">
                    <type>text</type>
                    <cols>48</cols>
                    <rows>5</rows>
                    <enableRichtext>1</enableRichtext>
                    <richtextConfiguration>default</richtextConfiguration>
                </config>
            </settings.bodytext>
        </el>
    </ROOT>
</T3DataStructure>';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

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

        $actionService = new ActionService();
        $actionService->modifyRecords(1, [
            'tt_content' => [
                'uid' => self::VALUE_ContentId,
                'pi_flexform' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'settings.isNotDefined' => '1',
                                'settings.bodytext' => [
                                    'vDEF' => '<p class="align-right">First line</p>

<p>Last line</p>',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $flexFormContent = $queryBuilder
            ->select('pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(self::VALUE_ContentId, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        self::assertEquals($expected, $flexFormContent);
    }

    #[Test]
    public function transformationAppliesForRichTextFieldsWithSheets(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'] = '<T3DataStructure>
    <meta>
        <langDisable>1</langDisable>
    </meta>
    <sheets>
        <sheet1>
            <ROOT>
                <sheetTitle>Text Example with an RTE field</sheetTitle>
                <type>array</type>
                <el>
                    <settings.bodytext>
                        <label>Random Bodytext</label>
                        <config>
                            <type>text</type>
                            <rows>5</rows>
                            <cols>30</cols>
                            <required>1</required>
                            <eval>trim</eval>
                            <enableRichtext>1</enableRichtext>
                            <richtextConfiguration>default</richtextConfiguration>
                        </config>
                    </settings.bodytext>
                </el>
            </ROOT>
        </sheet1>
    </sheets>
</T3DataStructure>';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

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

        $actionService = new ActionService();
        $actionService->modifyRecords(1, [
            'tt_content' => [
                'uid' => self::VALUE_ContentId,
                'pi_flexform' => [
                    'data' => [
                        'sheet1' => [
                            'lDEF' => [
                                'settings.isNotDefined' => '1',
                                'settings.bodytext' => [
                                    'vDEF' => '<p class="align-right">First line</p>

<p>Last line</p>',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $flexFormContent = $queryBuilder
            ->select('pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(self::VALUE_ContentId, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        self::assertEquals($expected, $flexFormContent);
    }
}
