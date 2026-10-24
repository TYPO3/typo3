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

namespace TYPO3\CMS\Core\Tests\Functional\Schema;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Schema\Field\FlexFormFieldType;
use TYPO3\CMS\Core\Schema\Field\GroupFieldType;
use TYPO3\CMS\Core\Schema\Field\InlineFieldType;
use TYPO3\CMS\Core\Schema\Field\SelectRelationFieldType;
use TYPO3\CMS\Core\Schema\FlexFormSchema;
use TYPO3\CMS\Core\Schema\FlexFormSchemaFactory;
use TYPO3\CMS\Core\Schema\RelationMap;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FlexFormSchemaFactoryTest extends FunctionalTestCase
{
    private const DATA_STRUCTURE = '<T3DataStructure>
        <sheets>
            <sDEF>
                <ROOT>
                    <sheetTitle>Relations</sheetTitle>
                    <type>array</type>
                    <el>
                        <group_field>
                            <label>Group</label>
                            <config>
                                <type>group</type>
                                <allowed>tx_test_target</allowed>
                            </config>
                        </group_field>
                        <mm_field>
                            <label>MM</label>
                            <config>
                                <type>select</type>
                                <renderType>selectMultipleSideBySide</renderType>
                                <foreign_table>tx_test_target</foreign_table>
                                <MM>tx_test_flex_target_mm</MM>
                            </config>
                        </mm_field>
                        <inline_field>
                            <label>Inline</label>
                            <config>
                                <type>inline</type>
                                <foreign_table>tx_test_target</foreign_table>
                                <foreign_field>parent_uid</foreign_field>
                            </config>
                        </inline_field>
                        <unknown_field>
                            <label>Unknown</label>
                            <config>
                                <type>notAvailable</type>
                            </config>
                        </unknown_field>
                    </el>
                </ROOT>
            </sDEF>
        </sheets>
    </T3DataStructure>';

    private const INVALID_DATA_STRUCTURE = '<T3DataStructure>
        <sheets>
            <sDEF>
                <ROOT>
                    <type>array</type>
                    <el>
                        <array_without_section>
                            <type>array</type>
                        </array_without_section>
                    </el>
                </ROOT>
            </sDEF>
        </sheets>
    </T3DataStructure>';
    protected bool $initializeDatabase = false;

    #[Test]
    public function getSchemaForRowResolvesRelationsOfEachSheetField(): void
    {
        $flexSchema = $this->getSchemaForDataStructure(self::DATA_STRUCTURE);

        self::assertInstanceOf(FlexFormSchema::class, $flexSchema);

        $groupField = $flexSchema->getField('group_field', 'sDEF');
        self::assertInstanceOf(GroupFieldType::class, $groupField);
        self::assertCount(1, $groupField->getRelations());
        self::assertSame('tx_test_target', $groupField->getRelations()[0]->toTable());
        self::assertFalse($groupField->getRelations()[0]->isManyToMany());

        $manyToManyField = $flexSchema->getField('mm_field', 'sDEF');
        self::assertInstanceOf(SelectRelationFieldType::class, $manyToManyField);
        self::assertCount(1, $manyToManyField->getRelations());
        self::assertSame('tx_test_target', $manyToManyField->getRelations()[0]->toTable());
        self::assertSame('tx_test_flex_target_mm', $manyToManyField->getRelations()[0]->manyToManyTable());

        $inlineField = $flexSchema->getField('inline_field', 'sDEF');
        self::assertInstanceOf(InlineFieldType::class, $inlineField);
        self::assertCount(1, $inlineField->getRelations());
        self::assertSame('tx_test_target', $inlineField->getRelations()[0]->toTable());
        self::assertSame('parent_uid', $inlineField->getRelations()[0]->toField());
    }

    #[Test]
    public function getSchemaForRowSkipsFieldsOfUnavailableType(): void
    {
        $flexSchema = $this->getSchemaForDataStructure(self::DATA_STRUCTURE);

        self::assertInstanceOf(FlexFormSchema::class, $flexSchema);
        self::assertNull($flexSchema->getField('unknown_field', 'sDEF'));
        self::assertCount(3, $flexSchema->getFields());
    }

    #[Test]
    public function getSchemaForRowReturnsNullForInvalidDataStructure(): void
    {
        self::assertNull($this->getSchemaForDataStructure(self::INVALID_DATA_STRUCTURE));
    }

    #[Test]
    public function getSchemaForRecordResolvesSameSchemaAsGetSchemaForRow(): void
    {
        $this->buildSchema(self::DATA_STRUCTURE);
        $field = $this->get(TcaSchemaFactory::class)->get('tx_test_flex')->getField('settings');
        self::assertInstanceOf(FlexFormFieldType::class, $field);
        $row = ['uid' => 1, 'pid' => 1, 'settings' => ''];

        $subject = $this->get(FlexFormSchemaFactory::class);
        $fromRecord = $subject->getSchemaForRecord(new RawRecord(1, 1, $row, new ComputedProperties(), 'tx_test_flex'), $field, new RelationMap());

        self::assertEquals($subject->getSchemaForRow('tx_test_flex', $field, $row), $fromRecord);
    }

    private function getSchemaForDataStructure(string $dataStructure): ?FlexFormSchema
    {
        $this->buildSchema($dataStructure);
        $field = $this->get(TcaSchemaFactory::class)->get('tx_test_flex')->getField('settings');
        self::assertInstanceOf(FlexFormFieldType::class, $field);
        return $this->get(FlexFormSchemaFactory::class)->getSchemaForRow('tx_test_flex', $field, ['uid' => 1, 'pid' => 1, 'settings' => '']);
    }

    private function buildSchema(string $dataStructure): void
    {
        $tca = $GLOBALS['TCA'];
        $tca['tx_test_flex'] = [
            'ctrl' => ['title' => 'Flex'],
            'columns' => [
                'settings' => [
                    'label' => 'Settings',
                    'config' => [
                        'type' => 'flex',
                        'ds' => $dataStructure,
                    ],
                ],
            ],
        ];
        $tca['tx_test_target'] = [
            'ctrl' => ['title' => 'Target'],
            'columns' => [
                'parent_uid' => [
                    'label' => 'Parent',
                    'config' => ['type' => 'passthrough'],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($tca);
    }
}
