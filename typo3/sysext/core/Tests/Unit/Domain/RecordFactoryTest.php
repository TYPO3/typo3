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

namespace TYPO3\CMS\Core\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\RecordFieldTransformer;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RecordFactoryTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function createFromDatabaseRowThrowsExceptionWhenTableIsNotTcaTable(): void
    {
        $this->expectExceptionCode(1715266929);
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isString())->willReturn(false);
        $schemaFactory = new TcaSchemaFactory(
            new RelationMapBuilder($this->createMock(FlexFormTools::class)),
            new FieldTypeFactory(),
            '',
            $cacheMock
        );
        $schemaFactory->load(['existing_schema' => ['ctrl' => [], 'columns' => []]]);
        $subject = new RecordFactory(
            $schemaFactory,
            $this->createMock(RecordFieldTransformer::class),
            $this->createMock(EventDispatcherInterface::class),
        );
        $subject->createFromDatabaseRow('foo', ['foo' => 1]);
    }

    #[Test]
    public function createFromDatabaseRowAddsTypeField(): void
    {
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isString())->willReturn(false);
        $schemaFactory = new TcaSchemaFactory(
            new RelationMapBuilder($this->createMock(FlexFormTools::class)),
            new FieldTypeFactory(),
            '',
            $cacheMock
        );
        $schemaFactory->load([
            'foo' => [
                'ctrl' => ['type' => 'type', 'crdate' => 'crdate'],
                'columns' => ['type' => ['config' => ['type' => 'select', 'items' => [['value' => 'bar', 'label' => 'bar']]]]],
                'types' => ['bar' => ['showitem' => 'type']],
            ],
        ]);
        $subject = new RecordFactory(
            $schemaFactory,
            $this->createMock(RecordFieldTransformer::class),
            $this->createMock(EventDispatcherInterface::class),
        );
        $time = time();
        /** @var Record $recordObject */
        $recordObject = $subject->createFromDatabaseRow('foo', ['uid' => 1, 'pid' => 2, 'type' => 'bar', 'crdate' => $time]);
        self::assertEquals('bar', $recordObject->toArray()['type']);
        self::assertEquals($time, $recordObject->toArray(true)['_system']['createdAt']->getTimestamp());
        self::assertEquals('bar', $recordObject->get('type'));
    }

    #[Test]
    public function resolvedRecordOnlyContainsFieldsInSubSchema(): void
    {
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isString())->willReturn(false);
        $schemaFactory = new TcaSchemaFactory(
            new RelationMapBuilder($this->createMock(FlexFormTools::class)),
            new FieldTypeFactory(),
            '',
            $cacheMock
        );
        $schemaFactory->load([
            'foo' => [
                'ctrl' => ['type' => 'type'],
                'columns' => ['type' => ['config' => ['type' => 'select', 'items' => [['value' => 'bar', 'label' => 'bar']]]], 'foo' => ['config' => ['type' => 'input']], 'bar' => ['config' => ['type' => 'input']]],
                'types' => ['foo' => ['showitem' => 'foo']],
            ],
        ]);
        $subject = new RecordFactory(
            $schemaFactory,
            $this->createMock(RecordFieldTransformer::class),
            $this->createMock(EventDispatcherInterface::class),
        );
        /** @var Record $recordObject */
        $recordObject = $subject->createFromDatabaseRow('foo', ['uid' => 1, 'pid' => 2, 'type' => 'foo', 'foo' => 'fooValue', 'bar' => 'barValue']);
        self::assertFalse($recordObject->has('bar'));
        self::assertTrue($recordObject->has('foo'));
        self::assertIsArray($recordObject->toArray(true)['_system']);
        self::assertTrue($recordObject->getRawRecord()->has('foo'));
        self::assertTrue($recordObject->getRawRecord()->has('bar'));
    }

    #[Test]
    public function createRawRecordCanBeCalledOnArrayRepresentationOfRawRecord(): void
    {
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isString())->willReturn(false);
        $schemaFactory = new TcaSchemaFactory(
            new RelationMapBuilder($this->createMock(FlexFormTools::class)),
            new FieldTypeFactory(),
            '',
            $cacheMock
        );
        $schemaFactory->load(
            [
                'foo' => [
                    'ctrl' => ['type' => 'type'],
                    'columns' => [
                        'type' => [
                            'config' => [
                                'type' => 'select',
                                'items' => [['value' => 'bar', 'label' => 'bar']],
                            ],
                        ],
                        'foo' => ['config' => ['type' => 'input']],
                        'bar' => ['config' => ['type' => 'input']],
                    ],
                    'types' => ['foo' => ['showitem' => 'foo']],
                ],
            ]
        );
        $subject = new RecordFactory(
            $schemaFactory,
            $this->createMock(RecordFieldTransformer::class),
            $this->createMock(EventDispatcherInterface::class),
        );
        $rawRecord = $subject->createRawRecord(
            'foo',
            [
                'uid' => 1,
                'pid' => 2,
                'type' => 'foo',
                'foo' => 'fooValue',
                'bar' => 'barValue',
                '_ORIG_uid' => 111,
                '_LOCALIZED_UID' => 112,
                '_REQUESTED_OVERLAY_LANGUAGE' => 2,
                '_TRANSLATION_SOURCE' => new Page(['uid' => 222]),
            ]
        );
        $arrayRepresentation = $rawRecord->toArray(true);
        $rawRecord2 = $subject->createRawRecord('foo', $arrayRepresentation);

        self::assertSame(111, $rawRecord2->getComputedProperties()->getVersionedUid());
        self::assertSame(112, $rawRecord2->getComputedProperties()->getLocalizedUid());
        self::assertSame(2, $rawRecord2->getComputedProperties()->getRequestedOverlayLanguageId());
        self::assertSame(['uid' => 222], $rawRecord2->getComputedProperties()->getTranslationSource()->toArray());
    }
}
