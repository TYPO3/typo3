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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\Exception\FlexFieldPropertyException;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;
use TYPO3\CMS\Core\Domain\RecordPropertyClosure;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FlexFormFieldValuesTest extends UnitTestCase
{
    #[Test]
    public function groupSpreadAcrossSheetsIsMergedWithoutSheetName(): void
    {
        $subject = new FlexFormFieldValues([
            'sDEF' => ['settings' => ['storagePid' => '123', 'list' => ['limit' => '5']]],
            'sheet2' => ['settings' => ['layout' => 'Layout 1', 'list' => ['order' => 'asc']]],
        ]);

        $expected = [
            'storagePid' => '123',
            'list' => ['limit' => '5', 'order' => 'asc'],
            'layout' => 'Layout 1',
        ];
        self::assertTrue($subject->has('settings'));
        self::assertSame($expected, $subject->get('settings'));
        self::assertSame($expected, $subject['settings']);
        self::assertSame(['limit' => '5', 'order' => 'asc'], $subject->get('settings.list'));
        self::assertSame('Layout 1', $subject->get('settings.layout'));
        self::assertSame(['storagePid' => '123', 'list' => ['limit' => '5']], $subject->get('sDEF/settings'));
        self::assertSame(['layout' => 'Layout 1', 'list' => ['order' => 'asc']], $subject->get('sheet2/settings'));
    }

    #[Test]
    public function recordPropertyClosuresInMergedGroupAreResolvedOnce(): void
    {
        $instantiations = 0;
        $object = new \stdClass();
        $subject = new FlexFormFieldValues([
            'sDEF' => ['settings' => ['relation' => new RecordPropertyClosure(static function () use (&$instantiations, $object): \stdClass {
                $instantiations++;
                return $object;
            })]],
            'sheet2' => ['settings' => ['layout' => 'Layout 1']],
        ]);

        self::assertSame(['relation' => $object, 'layout' => 'Layout 1'], $subject->get('settings'));
        self::assertSame($object, $subject->get('settings.relation'));
        self::assertSame($object, $subject->get('sDEF/settings.relation'));
        self::assertSame(1, $instantiations);
    }

    #[Test]
    public function resolvedRecordPropertyClosureIsReused(): void
    {
        $instantiations = 0;
        $subject = new FlexFormFieldValues([
            'sDEF' => ['link' => new RecordPropertyClosure(static function () use (&$instantiations): \stdClass {
                $instantiations++;
                return new \stdClass();
            })],
        ]);

        self::assertSame($subject->get('link'), $subject->get('link'));
        self::assertSame(1, $instantiations);
    }

    #[Test]
    public function recordPropertyClosureResolvingToNullIsResolvedOnce(): void
    {
        $instantiations = 0;
        $subject = new FlexFormFieldValues([
            'sDEF' => ['link' => new RecordPropertyClosure(static function () use (&$instantiations): null {
                $instantiations++;
                return null;
            })],
        ]);

        self::assertNull($subject->get('link'));
        self::assertNull($subject->get('link'));
        self::assertSame(1, $instantiations);
    }

    #[Test]
    public function resolvingPropertiesDoesNotChangeSheets(): void
    {
        $closure = new RecordPropertyClosure(static fn(): array => ['thing' => 'value']);
        $sheets = [
            'sDEF' => ['settings' => ['config' => $closure]],
            'sheet2' => ['settings' => ['config' => ['other' => 'value']]],
        ];
        $subject = new FlexFormFieldValues($sheets);

        self::assertSame(['thing' => 'value'], $subject->get('sDEF/settings.config'));
        self::assertSame($sheets, $subject->toArray());
        // A resolved property does not turn into a group, which could be merged with another sheet
        $this->expectException(FlexFieldPropertyException::class);
        $this->expectExceptionCode(1731962638);
        $subject->has('settings');
    }

    public static function propertyPathExistenceDataProvider(): \Generator
    {
        $sheets = [
            'sDEF' => ['settings' => ['list' => ['limit' => '5']], 'header' => 'a'],
            'sheet2' => ['settings' => ['layout' => 'Layout 1']],
        ];
        yield 'deep path existing in one sheet' => [$sheets, 'settings.list.limit', true];
        yield 'missing path' => [$sheets, 'settings.missing', false];
        yield 'missing sheet' => [$sheets, 'missing/header', false];
        yield 'path not existing in given sheet' => [$sheets, 'sheet2/header', false];
        yield 'empty sheet name' => [$sheets, '/header', true];
        yield 'empty sheet name in single sheet' => [['sDEF' => ['header' => 'a']], '/header', true];
        yield 'numeric sheet name' => [[0 => ['header' => 'a']], 'header', true];
        yield 'no sheets' => [[], 'header', false];
    }

    #[Test]
    #[DataProvider('propertyPathExistenceDataProvider')]
    public function hasReturnsWhetherPropertyPathExists(array $sheets, string $id, bool $expected): void
    {
        $subject = new FlexFormFieldValues($sheets);
        self::assertSame($expected, $subject->has($id));
    }

    public static function ambiguousPropertyPathDataProvider(): \Generator
    {
        yield 'same field in two sheets' => [
            ['sDEF' => ['header' => 'a'], 'sheet2' => ['header' => 'b']],
            'header',
        ];
        yield 'same grouped field in two sheets' => [
            ['sDEF' => ['settings' => ['limit' => '1']], 'sheet2' => ['settings' => ['limit' => '2']]],
            'settings',
        ];
        yield 'same nested grouped field in two sheets' => [
            ['sDEF' => ['settings' => ['list' => ['limit' => '1']]], 'sheet2' => ['settings' => ['list' => ['limit' => '2']]]],
            'settings',
        ];
        yield 'field in one sheet, group of the same name in another sheet' => [
            ['sDEF' => ['some' => 'value'], 'sheet2' => ['some' => ['thing' => 'value']]],
            'some',
        ];
        yield 'list value in one sheet, group of the same name in another sheet' => [
            ['sDEF' => ['settings' => ['a', 'b']], 'sheet2' => ['settings' => ['thing' => 'value']]],
            'settings',
        ];
        yield 'empty list value in one sheet, group of the same name in another sheet' => [
            ['sDEF' => ['settings' => []], 'sheet2' => ['settings' => ['thing' => 'value']]],
            'settings',
        ];
        yield 'object value in one sheet, group of the same name in another sheet' => [
            ['sDEF' => ['settings' => new RecordPropertyClosure(static fn(): array => ['thing' => 'value'])], 'sheet2' => ['settings' => ['other' => 'value']]],
            'settings',
        ];
    }

    #[Test]
    #[DataProvider('ambiguousPropertyPathDataProvider')]
    public function ambiguousPropertyPathWithoutSheetNameThrowsException(array $sheets, string $id): void
    {
        $this->expectException(FlexFieldPropertyException::class);
        $this->expectExceptionCode(1731962638);
        $subject = new FlexFormFieldValues($sheets);
        $subject->has($id);
    }

    #[Test]
    public function ambiguousPropertyPathCanBeResolvedWithSheetName(): void
    {
        $subject = new FlexFormFieldValues([
            'sDEF' => ['some' => 'value'],
            'sheet2' => ['some' => ['thing' => 'other value']],
        ]);

        self::assertSame('value', $subject->get('sDEF/some'));
        self::assertSame(['thing' => 'other value'], $subject->get('sheet2/some'));
        self::assertSame('other value', $subject->get('some.thing'));
    }
}
