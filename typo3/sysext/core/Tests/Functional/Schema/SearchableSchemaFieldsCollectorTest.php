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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\NumberFieldType;
use TYPO3\CMS\Core\Schema\SearchableSchemaFieldsCollector;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SearchableSchemaFieldsCollectorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function emptyFieldCollectionForUnknownSchema(): void
    {
        $fieldsCollector = $this->getContainer()->get(SearchableSchemaFieldsCollector::class);
        self::assertCount(0, $fieldsCollector->getFields('unknown'));
    }

    #[Test]
    public function emptyFieldCollectionForEmptySearchFields(): void
    {
        $schemaFactory = $this->getContainer()->get(TcaSchemaFactory::class);
        $schemaFactory->rebuild(
            [
                'aTable' => [
                    'ctrl' => [
                        'searchFields' => '',
                    ],
                ],
            ]
        );

        $fieldsCollector = $this->getContainer()->get(SearchableSchemaFieldsCollector::class);
        self::assertCount(0, $fieldsCollector->getFields('aTable'));
    }

    #[Test]
    public function searchFieldsAreReturned(): void
    {
        $schemaFactory = $this->getContainer()->get(TcaSchemaFactory::class);
        $schemaFactory->rebuild(
            [
                'aTable' => [
                    'ctrl' => [
                        'searchFields' => 'foo,bar,baz',
                    ],
                    'columns' => [
                        'foo' => ['config' => ['type' => 'input']],
                        'bar' => ['config' => ['type' => 'none']],
                    ],
                ],
            ]
        );

        // Returns only foo as only this field is searchable. "bar"
        // exists but is not searchable and baz does not exists at all.

        $fieldsCollector = $this->getContainer()->get(SearchableSchemaFieldsCollector::class);
        $fields = $fieldsCollector->getFields('aTable');
        self::assertCount(1, $fields);
        $fieldsArray = iterator_to_array($fields);
        self::assertEquals('foo', $fieldsArray['foo']->getName());
        self::assertEquals('input', $fieldsArray['foo']->getType());
        self::assertEquals(['foo'], array_values($fieldsCollector->getFieldNames('aTable')));
    }

    #[Test]
    public function specialSearchFieldsAreReturned(): void
    {
        $schemaFactory = $this->getContainer()->get(TcaSchemaFactory::class);
        $schemaFactory->rebuild(
            [
                'aTable' => [
                    'ctrl' => [
                        'searchFields' => 'numberInteger,numberDecimal,datetimeWithoutDbType,datetimeWithDbType',
                    ],
                    'columns' => [
                        'numberInteger' => ['config' => ['type' => 'number', 'format' => 'integer']],
                        'numberFloat' => ['config' => ['type' => 'number', 'format' => 'decimal']],
                        'datetimeWithoutDbType' => ['config' => ['type' => 'datetime']],
                        'datetimeWithDbType' => ['config' => ['type' => 'datetime', 'dbType' => 'datetime']],
                    ],
                ],
            ]
        );

        // Returns only foo as only this field is searchable. "bar"
        // exists but is not searchable and baz does not exists at all.

        $fieldsCollector = $this->getContainer()->get(SearchableSchemaFieldsCollector::class);
        $fields =  $fieldsCollector->getFields('aTable');
        self::assertCount(2, $fields);
        $fieldsArray = iterator_to_array($fields);
        /** @var NumberFieldType $number */
        $number = $fieldsArray['numberInteger'];
        self::assertEquals('numberInteger', $number->getName());
        self::assertEquals('number', $number->getType());
        self::assertEquals('integer', $number->getFormat());
        /** @var DateTimeFieldType $datetime */
        $datetime = $fieldsArray['datetimeWithoutDbType'];
        self::assertEquals('datetimeWithoutDbType', $datetime->getName());
        self::assertEquals('datetime', $datetime->getType());
        self::assertNull($datetime->getPersistenceType());
        self::assertEquals(['numberInteger', 'datetimeWithoutDbType'], array_values($fieldsCollector->getFieldNames('aTable')));
    }

    #[Test]
    public function manualSearchFieldsAreReturned(): void
    {
        $schemaFactory = $this->getContainer()->get(TcaSchemaFactory::class);
        $schemaFactory->rebuild(
            [
                'aTable' => [
                    'ctrl' => [
                        'searchFields' => 'foo,bar,baz',
                    ],
                    'columns' => [
                        'foo' => ['config' => ['type' => 'input']],
                        'bar' => ['config' => ['type' => 'input']],
                        'baz' => ['config' => ['type' => 'input']],
                        'foobar' => ['config' => ['type' => 'input']],
                    ],
                ],
            ]
        );

        $manualSearchFields = ['foo', 'foobar', 'bazbar'];
        $fieldsCollector = $this->getContainer()->get(SearchableSchemaFieldsCollector::class);
        $fields =  $fieldsCollector->getFields('aTable', $manualSearchFields);
        self::assertCount(2, $fields);
        $fieldsArray = iterator_to_array($fields);
        self::assertEquals('foo', $fieldsArray['foo']->getName());
        self::assertEquals('input', $fieldsArray['foo']->getType());
        self::assertEquals('foobar', $fieldsArray['foobar']->getName());
        self::assertEquals('input', $fieldsArray['foobar']->getType());
        self::assertEquals(['foo', 'foobar'], array_values($fieldsCollector->getFieldNames('aTable', $manualSearchFields)));
    }

    public static function uniqueFieldListIsReturnedDataProvider(): \Generator
    {
        yield 'default behaviour' => [
            ['foo', 'bar'],
            [],
            false,
        ];
        // @todo 'baz' is added even if not defined in "columns" -> We might want to change this?
        yield 'existing fields' => [
            ['baz', 'foo', 'bar'],
            ['baz'],
            false,
        ];
        yield 'duplicate field is replaced' => [
            ['bar', 'foo'],
            ['bar'],
            false,
        ];
        yield 'special fields are added' => [
            ['uid', 'pid', 'foo', 'bar'],
            [],
            true,
        ];
        yield 'existing fields are added before special fields' => [
            ['baz', 'uid', 'pid', 'foo', 'bar'],
            ['baz'],
            true,
        ];
    }

    #[DataProvider('uniqueFieldListIsReturnedDataProvider')]
    #[Test]
    public function uniqueFieldListIsReturned(array $expected, array $existingFields, bool $includeSpecialFields): void
    {
        $schemaFactory = $this->getContainer()->get(TcaSchemaFactory::class);
        $schemaFactory->rebuild(
            [
                'aTable' => [
                    'ctrl' => [
                        'searchFields' => 'foo,bar',
                    ],
                    'columns' => [
                        'foo' => ['config' => ['type' => 'input']],
                        'bar' => ['config' => ['type' => 'uuid']],
                    ],
                ],
            ]
        );

        $fieldsCollector = $this->getContainer()->get(SearchableSchemaFieldsCollector::class);
        self::assertEquals($expected, array_values($fieldsCollector->getUniqueFieldList('aTable', $existingFields, $includeSpecialFields)));
    }
}
