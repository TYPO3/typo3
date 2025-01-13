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
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RawRecordTest extends UnitTestCase
{
    public static function getTypeReturnsExpectedValueDataProvider(): iterable
    {
        yield 'full type' => [
            'tt_content',
            'tt_content',
            'tt_content',
            null,
        ];
        yield 'full type with dot' => [
            'tt_content.',
            'tt_content.',
            'tt_content',
            null,
        ];
        yield 'full type with dot and zero' => [
            'tt_content.0',
            'tt_content.0',
            'tt_content',
            '0',
        ];
        yield 'record type' => [
            'tt_content.text',
            'tt_content.text',
            'tt_content',
            'text',
            'list',
        ];
        yield 'record type with dot' => [
            'tt_content.record.type',
            'tt_content.record.type',
            'tt_content',
            'record.type',
        ];
        yield 'record type with dot at the end' => [
            'tt_content.record.',
            'tt_content.record.',
            'tt_content',
            'record.',
        ];
        yield 'record type with just dots - uncommon but supported' => [
            'tt_content....',
            'tt_content....',
            'tt_content',
            '...',
        ];
    }

    #[DataProvider('getTypeReturnsExpectedValueDataProvider')]
    #[Test]
    public function getTypeReturnsExpectedParts(string $type, string $fullType, string $mainType, ?string $recordType): void
    {
        $record = new RawRecord(123, 456, [], $this->createMock(ComputedProperties::class), $type);

        self::assertSame($fullType, $record->getFullType());
        self::assertSame($mainType, $record->getMainType());
        self::assertSame($recordType, $record->getRecordType());
    }
}
