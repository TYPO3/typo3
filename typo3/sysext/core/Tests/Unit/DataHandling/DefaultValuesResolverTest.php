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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\DefaultValuesResolver;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DefaultValuesResolverTest extends UnitTestCase
{
    private TcaSchemaFactory $tcaSchemaFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $cache = self::createStub(PhpFrontend::class);
        $cache->method('has')->willReturn(false);
        $this->tcaSchemaFactory = new TcaSchemaFactory(
            new TcaSchemaBuilder(
                new RelationMapBuilder(self::createStub(FlexFormTools::class)),
                new FieldTypeFactory(),
            ),
            '',
            $cache
        );
    }

    public static function resolveReturnsDefaultValuesOfTcaDataProvider(): \Generator
    {
        yield 'slug column' => ['column' => 'slug_1', 'expected' => ''];
        yield 'slug column (no such config)' => ['column' => 'slug_2', 'expected' => ''];
        yield 'uuid column' => ['column' => 'uuid_1', 'expected' => ''];
        yield 'uuid column (wrong config)' => ['column' => 'uuid_2', 'expected' => ''];
        yield 'input with default string' => ['column' => 'input_1', 'expected' => 'testdefault'];
        yield 'input with default integer' => ['column' => 'input_2', 'expected' => 5];
        yield 'input with default null' => ['column' => 'input_3', 'expected' => null];
    }

    #[DataProvider('resolveReturnsDefaultValuesOfTcaDataProvider')]
    #[Test]
    public function resolveReturnsDefaultValuesOfTca(string $column, mixed $expected): void
    {
        $this->tcaSchemaFactory->load([
            'tx_my_testtable' => [
                'columns' => [
                    'slug_1' => ['config' => ['type' => 'slug']],
                    'slug_2' => ['config' => ['type' => 'slug', 'default' => 'shouldnotbeset']],
                    'uuid_1' => ['config' => ['type' => 'uuid']],
                    'uuid_2' => ['config' => ['type' => 'uuid', 'default' => 'nosuchconfig']],
                    'input_1' => ['config' => ['type' => 'input', 'default' => 'testdefault']],
                    'input_2' => ['config' => ['type' => 'input', 'default' => 5]],
                    'input_3' => ['config' => ['type' => 'input', 'default' => null, 'nullable' => true]],
                ],
            ],
        ], true);
        $defaultValues = new DefaultValuesResolver($this->tcaSchemaFactory)->resolve('tx_my_testtable', [], [], []);
        self::assertArrayHasKey($column, $defaultValues);
        self::assertEquals($expected, $defaultValues[$column]);
    }

    public static function resolveSkipsInvalidDefaultValuesDataProvider(): \Generator
    {
        yield 'input default null without nullable' => ['column' => 'input_1'];
        yield 'input default null with nullable false' => ['column' => 'input_2'];
        yield 'check with nullable true and default null' => ['column' => 'check_1'];
        yield 'file with default value' => ['column' => 'file_1'];
    }

    #[DataProvider('resolveSkipsInvalidDefaultValuesDataProvider')]
    #[Test]
    public function resolveSkipsInvalidDefaultValues(string $column): void
    {
        $this->tcaSchemaFactory->load([
            'tx_my_testtable' => [
                'columns' => [
                    'input_1' => ['config' => ['type' => 'input', 'default' => null]],
                    'input_2' => ['config' => ['type' => 'input', 'default' => null, 'nullable' => false]],
                    'check_1' => ['config' => ['type' => 'check', 'default' => null, 'nullable' => true]],
                    'file_1' => ['config' => ['type' => 'file', 'default' => 'nosuchconfig']],
                    'input_3' => ['config' => ['type' => 'input', 'default' => 'test']],
                ],
            ],
        ], true);
        $defaultValues = new DefaultValuesResolver($this->tcaSchemaFactory)->resolve('tx_my_testtable', [], [], []);
        self::assertEquals('test', $defaultValues['input_3']);
        self::assertArrayNotHasKey($column, $defaultValues);
    }

    public static function resolveTakesDefaultValueFromSourceWithHighestPrecedenceDataProvider(): \Generator
    {
        yield 'TCA default' => [
            'incomingFieldArray' => [],
            'pageTsConfigTcaDefaults' => [],
            'userTsConfigTcaDefaults' => [],
            'expected' => 'TCA default',
        ];
        yield 'TCA default of record type' => [
            'incomingFieldArray' => ['record_type' => 'b'],
            'pageTsConfigTcaDefaults' => [],
            'userTsConfigTcaDefaults' => [],
            'expected' => 'TCA default for type b',
        ];
        yield 'user TSconfig overrides TCA default of record type' => [
            'incomingFieldArray' => ['record_type' => 'b'],
            'pageTsConfigTcaDefaults' => [],
            'userTsConfigTcaDefaults' => ['tx_test.' => ['title' => 'user']],
            'expected' => 'user',
        ];
        yield 'user TSconfig of record type overrides user TSconfig' => [
            'incomingFieldArray' => ['record_type' => 'b'],
            'pageTsConfigTcaDefaults' => [],
            'userTsConfigTcaDefaults' => ['tx_test.' => ['title' => 'user', 'title.' => ['types.' => ['b' => 'user for type b']]]],
            'expected' => 'user for type b',
        ];
        yield 'page TSconfig overrides user TSconfig of record type' => [
            'incomingFieldArray' => ['record_type' => 'b'],
            'pageTsConfigTcaDefaults' => ['tx_test.' => ['title' => 'page']],
            'userTsConfigTcaDefaults' => ['tx_test.' => ['title.' => ['types.' => ['b' => 'user for type b']]]],
            'expected' => 'page',
        ];
        yield 'page TSconfig of record type overrides page TSconfig' => [
            'incomingFieldArray' => ['record_type' => 'b'],
            'pageTsConfigTcaDefaults' => ['tx_test.' => ['title' => 'page', 'title.' => ['types.' => ['b' => 'page for type b']]]],
            'userTsConfigTcaDefaults' => [],
            'expected' => 'page for type b',
        ];
        yield 'record type from page TSconfig' => [
            'incomingFieldArray' => [],
            'pageTsConfigTcaDefaults' => ['tx_test.' => ['record_type' => 'b', 'title.' => ['types.' => ['b' => 'page for type b']]]],
            'userTsConfigTcaDefaults' => [],
            'expected' => 'page for type b',
        ];
        yield 'record type from user TSconfig' => [
            'incomingFieldArray' => [],
            'pageTsConfigTcaDefaults' => [],
            'userTsConfigTcaDefaults' => ['tx_test.' => ['record_type' => 'b']],
            'expected' => 'TCA default for type b',
        ];
        yield 'invalid record type is skipped' => [
            'incomingFieldArray' => ['record_type' => 'invalid'],
            'pageTsConfigTcaDefaults' => ['tx_test.' => ['record_type' => 'b']],
            'userTsConfigTcaDefaults' => [],
            'expected' => 'TCA default for type b',
        ];
    }

    #[DataProvider('resolveTakesDefaultValueFromSourceWithHighestPrecedenceDataProvider')]
    #[Test]
    public function resolveTakesDefaultValueFromSourceWithHighestPrecedence(
        array $incomingFieldArray,
        array $pageTsConfigTcaDefaults,
        array $userTsConfigTcaDefaults,
        string $expected
    ): void {
        $this->tcaSchemaFactory->load([
            'tx_test' => [
                'ctrl' => ['type' => 'record_type'],
                'columns' => [
                    'record_type' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['label' => 'a', 'value' => 'a'], ['label' => 'b', 'value' => 'b']],
                            'default' => 'a',
                        ],
                    ],
                    'title' => ['config' => ['type' => 'input', 'default' => 'TCA default']],
                ],
                'types' => [
                    'a' => ['showitem' => 'record_type, title'],
                    'b' => [
                        'showitem' => 'record_type, title',
                        'columnsOverrides' => ['title' => ['config' => ['default' => 'TCA default for type b']]],
                    ],
                ],
            ],
        ], true);
        $defaultValues = new DefaultValuesResolver($this->tcaSchemaFactory)
            ->resolve('tx_test', $incomingFieldArray, $pageTsConfigTcaDefaults, $userTsConfigTcaDefaults);
        self::assertSame($expected, $defaultValues['title']);
    }

    #[Test]
    public function resolveDoesNotTakeRecordTypeFromFieldPointingToRelatedRecord(): void
    {
        $this->tcaSchemaFactory->load([
            'tx_related' => [
                'ctrl' => ['type' => 'type'],
                'columns' => [
                    'type' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['label' => 'one', 'value' => '1'], ['label' => 'two', 'value' => '2']],
                        ],
                    ],
                ],
                'types' => [
                    '1' => ['showitem' => 'type'],
                    '2' => ['showitem' => 'type'],
                ],
            ],
            'tx_test' => [
                'ctrl' => ['type' => 'related:type'],
                'columns' => [
                    'related' => ['config' => ['type' => 'group', 'allowed' => 'tx_related', 'maxitems' => 1]],
                    'title' => ['config' => ['type' => 'input', 'default' => 'TCA default']],
                ],
                'types' => [
                    '1' => ['showitem' => 'related, title'],
                    '2' => [
                        'showitem' => 'related, title',
                        'columnsOverrides' => ['title' => ['config' => ['default' => 'TCA default for type 2']]],
                    ],
                ],
            ],
        ], true);
        // "2" is the uid of the related record, not a record type
        $defaultValues = new DefaultValuesResolver($this->tcaSchemaFactory)->resolve('tx_test', ['related' => 2], [], []);
        self::assertSame('TCA default', $defaultValues['title']);
    }
}
