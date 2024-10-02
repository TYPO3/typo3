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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Schema\VisibleSchemaFieldsCollector;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class VisibleSchemaFieldsCollectorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user['admin'] = 0;
    }

    #[Test]
    public function emptyFieldCollectionForUnknownSchema(): void
    {
        /** @var VisibleSchemaFieldsCollector $fieldsCollector */
        $fieldsCollector = $this->get(VisibleSchemaFieldsCollector::class);
        self::assertCount(0, $fieldsCollector->getFields('unknown', []));
    }

    #[Test]
    public function emptyFieldCollectionForEmptyRecord(): void
    {
        $schemaFactory = $this->getContainer()->get(TcaSchemaFactory::class);
        $schemaFactory->rebuild(
            [
                'aTable' => [
                    'ctrl' => [],
                ],
            ]
        );
        $fieldsCollector = $this->get(VisibleSchemaFieldsCollector::class);
        self::assertCount(0, $fieldsCollector->getFields('aTable', []));
    }

    #[Test]
    public function expectedFieldCollectionWithoutType(): void
    {
        $schemaFactory = $this->getContainer()->get(TcaSchemaFactory::class);
        $schemaFactory->rebuild(
            [
                'aTable' => [
                    'columns' => [
                        'foo' => ['config' => ['type' => 'input']],
                        'bar' => ['config' => ['type' => 'input']],
                    ],
                ],
            ]
        );
        $fieldsCollector = $this->get(VisibleSchemaFieldsCollector::class);
        $fieldCollection = iterator_to_array($fieldsCollector->getFields('aTable', ['uid' => 1, 'pid' => 1]));
        self::assertEquals(['foo', 'bar'], array_keys($fieldCollection));
    }

    public static function expectedFieldCollectionIsReturnedDataProvider(): \Generator
    {
        yield 'invalid type' => [
            'type,foo',
            ['type' => 'invalid'],
            [],
            ['type', 'foo', 'bar', 'baz', 'sys_language_uid', 'l10n_parent', 'subtype'],
        ];
        yield 'record type given' => [
            'type,foo',
            [],
            [],
            ['type', 'foo'],
        ];
        yield 'subtype given' => [
            'type,foo,subtype',
            ['subtype' => 'aSubTypeValue'],
            [],
            ['type', 'foo', 'subtype', 'baz'],
        ];
        yield 'exclude fields' => [
            'type,foo',
            [],
            ['foo'],
            ['type'],
        ];
        yield 'exclude subtype field' => [
            'type,foo,subtype',
            ['subtype' => 'aSubTypeValue'],
            ['subtype'],
            ['type', 'foo', 'baz'],
        ];
        yield 'translation with l10n_mode=exclude' => [
            'type,foo',
            ['sys_language_uid' => 1, 'l10n_parent' => 1],
            [],
            ['type'],
        ];
        yield 'user permission exclude field' => [
            'type,foo,exclude',
            [],
            [],
            ['type', 'foo'],
        ];
    }

    #[DataProvider('expectedFieldCollectionIsReturnedDataProvider')]
    #[Test]
    public function expectedFieldCollectionIsReturned(string $showitem, array $record, array $excludeFields, array $expectedFields): void
    {
        $schemaFactory = $this->get(TcaSchemaFactory::class);
        $schemaFactory->rebuild(
            [
                'aTable' => [
                    'ctrl' => [
                        'type' => 'type',
                        'languageField' => 'sys_language_uid',
                        'transOrigPointerField' => 'l10n_parent',
                    ],
                    'columns' => [
                        'type' => ['config' => ['type' => 'select', 'items' => [['label' => 'atype', 'value' => 'aType']]]],
                        'foo' => ['l10n_mode' => 'exclude', 'config' => ['type' => 'input']],
                        'bar' => ['config' => ['type' => 'input']],
                        'baz' => ['config' => ['type' => 'input']],
                        'exclude' => ['exclude' => true, 'config' => ['type' => 'input']],
                        'sys_language_uid' => ['config' => ['type' => 'language']],
                        'l10n_parent' => ['config' => ['type' => 'input']],
                        'subtype' => ['config' => ['type' => 'select', 'items' => [['label' => 'aSubType', 'value' => 'aSubTypeValue']]]],
                    ],
                    'types' => [
                        'aType' => [
                            'showitem' => $showitem,
                            'subtype_value_field' => 'subtype',
                            'subtypes_addlist' => [
                                'aSubTypeValue' => 'baz',
                            ],
                        ],
                    ],
                ],
            ]
        );
        $fieldsCollector = $this->get(VisibleSchemaFieldsCollector::class);
        $fieldCollection = iterator_to_array($fieldsCollector->getFields('aTable', array_replace_recursive([
            'uid' => 1, 'pid' => 1, 'sys_language_uid' => 0, 'l10n_parent' => 0, 'type' => 'aType', 'foo' => '123', 'bar' => '456', 'baz' => '789',
        ], $record), $excludeFields));
        self::assertEquals($expectedFields, array_keys($fieldCollection));
    }
}
