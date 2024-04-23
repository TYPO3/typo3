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

namespace TYPO3\CMS\Core\Tests\Unit\Log;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\LogDataTraitTestAccessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LogDataTraitTest extends UnitTestCase
{
    public static function formatLogDetailsLegacyStringsDataProvider(): \Generator
    {
        yield 'String with percent and empty argument %' => [
            'detailString' => 'String with percent and empty argument %',
            'substitutes' => [],
            'expectedResult' => 'String with percent and empty argument %',
        ];
        yield 'String with string %s and empty argument' => [
            'detailString' => 'String with string %s and empty argument',
            'substitutes' => [],
            'expectedResult' => 'String with string  and empty argument',
        ];
        yield 'String with decimal %d and empty argument' => [
            'detailString' => 'String with decimal %d and empty argument',
            'substitutes' => [],
            'expectedResult' => 'String with decimal %d and empty argument',
        ];
        yield 'String with percent and single argument %' => [
            'detailString' => 'String with percent and single argument %',
            'substitutes' => [0 => 'a argument'],
            'expectedResult' => 'String with percent and single argument %',
        ];
        yield 'String with string %s and single argument' => [
            'detailString' => 'String with string %s and single argument',
            'substitutes' => [0 => 'this is a string'],
            'expectedResult' => 'String with string this is a string and single argument',
        ];
        yield 'String with decimal %d and single argument' => [
            'detailString' => 'String with decimal %d and single argument',
            'substitutes' => [0 => 42],
            'expectedResult' => 'String with decimal 42 and single argument',
        ];
        yield 'String with percent and multiple arguments %' => [
            'detailString' => 'String with percent and multiple arguments %',
            'substitutes' => [0 => 'a argument', 1 => 'another argument'],
            'expectedResult' => 'String with percent and multiple arguments %',
        ];
        yield 'String with string %s and multiple arguments %s' => [
            'detailString' => 'String with string %s and multiple arguments %s',
            'substitutes' => [0 => 'this is a string', 1 => 'another string'],
            'expectedResult' => 'String with string this is a string and multiple arguments another string',
        ];
        yield 'String with decimal %d and multiple arguments %s' => [
            'detailString' => 'String with decimal %d and multiple arguments %s',
            'substitutes' => [0 => 42, 1 => 'another string'],
            'expectedResult' => 'String with decimal 42 and multiple arguments another string',
        ];
        yield 'String with percent and to many arguments %' => [
            'detailString' => 'String with percent and to many arguments %',
            'substitutes' => [0 => 'a argument', 1 => 'another argument', 2 => 'a third string'],
            'expectedResult' => 'String with percent and to many arguments %',
        ];
        yield 'String with string %s and to many arguments %s' => [
            'detailString' => 'String with string %s and to many arguments %s',
            'substitutes' => [0 => 'this is a string', 1 => 'another string', 2 => 'a third string'],
            'expectedResult' => 'String with string this is a string and to many arguments another string',
        ];
        yield 'String with decimal %d and to many arguments %s' => [
            'detailString' => 'String with decimal %d and to many arguments %s',
            'substitutes' => [0 => 42, 1 => 'another string', 2 => 'a third string'],
            'expectedResult' => 'String with decimal 42 and to many arguments another string',
        ];
        // %s is special since it's replaced with empty string now matter what
        yield 'String with string %s %s and empty argument' => [
            'detailString' => 'String with string %s %s and empty argument',
            'substitutes' => [],
            'expectedResult' => 'String with string   and empty argument',
        ];
        yield 'String with string %s %s and to few arguments' => [
            'detailString' => 'String with string %s %s and to few arguments',
            'substitutes' => [0 => 'astring'],
            'expectedResult' => 'String with string   and to few arguments',
        ];
        yield 'String with string %s %s and to many arguments' => [
            'detailString' => 'String with string %s %s and to many arguments',
            'substitutes' => [0 => 'astring', 1 => 'another string', 2 => 'a third string'],
            'expectedResult' => 'String with string astring another string and to many arguments',
        ];
    }

    #[DataProvider('formatLogDetailsLegacyStringsDataProvider')]
    #[Test]
    public function formatLogDetailsStaticLegacyStrings(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, LogDataTraitTestAccessor::callFormatLogDetailsStatic($detailString, $substitutes));
    }

    #[DataProvider('formatLogDetailsLegacyStringsDataProvider')]
    #[Test]
    public function formatLogDetailsLegacyStrings(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, (new LogDataTraitTestAccessor())->callFormatLogDetails($detailString, $substitutes));
    }

    #[DataProvider('formatLogDetailsLegacyStringsDataProvider')]
    #[Test]
    public function formatLogDetailsLegacyStringsWithSerializedSubstitutes(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, (new LogDataTraitTestAccessor())->callFormatLogDetails($detailString, serialize($substitutes)));
    }

    #[DataProvider('formatLogDetailsLegacyStringsDataProvider')]
    #[Test]
    public function formatLogDetailsLegacyStringsWithJsonEncodedSubstitutes(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, (new LogDataTraitTestAccessor())->callFormatLogDetails($detailString, json_encode($substitutes)));
    }

    public static function formatLogDetailsNewFormatStringsDataProvider(): \Generator
    {
        yield 'Empty arguments {aPlaceholder}' => [
            'detailString' => 'Empty arguments {aPlaceholder}',
            'substitutes' => [],
            'expectedResult' => 'Empty arguments {aPlaceholder}',
        ];
        yield 'Non existing arguments {aPlaceholder}' => [
            'detailString' => 'Non existing arguments {aPlaceholder}',
            'substitutes' => ['non-existing-argument' => 'non-existing-argument'],
            'expectedResult' => 'Non existing arguments {aPlaceholder}',
        ];
        yield 'Single argument {myPlaceholder}' => [
            'detailString' => 'Single argument {myPlaceholder}',
            'substitutes' => ['myPlaceholder' => 'replacedPlacerHolder'],
            'expectedResult' => 'Single argument replacedPlacerHolder',
        ];
        yield 'Multiple argument {myPlaceholder} {anotherPlaceholder}' => [
            'detailString' => 'Multiple argument {myPlaceholder} {anotherPlaceholder}',
            'substitutes' => ['myPlaceholder' => 'replacedPlacerHolder', 'anotherPlaceholder' => 'replacedAnotherPlaceholder'],
            'expectedResult' => 'Multiple argument replacedPlacerHolder replacedAnotherPlaceholder',
        ];
        yield 'Multiple argument {myPlaceholder} {anotherPlaceholder} to many arguments' => [
            'detailString' => 'Multiple argument {myPlaceholder} {anotherPlaceholder} to many arguments',
            'substitutes' => ['non-existing-argument' => 'non-existing-argument', 'myPlaceholder' => 'replacedPlacerHolder', 'anotherPlaceholder' => 'replacedAnotherPlaceholder'],
            'expectedResult' => 'Multiple argument replacedPlacerHolder replacedAnotherPlaceholder to many arguments',
        ];
    }

    #[DataProvider('formatLogDetailsNewFormatStringsDataProvider')]
    #[Test]
    public function formatLogDetailsStaticNewFormatStrings(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, LogDataTraitTestAccessor::callFormatLogDetailsStatic($detailString, $substitutes));
    }

    #[DataProvider('formatLogDetailsNewFormatStringsDataProvider')]
    #[Test]
    public function formatLogDetailsNewFormatStrings(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, (new LogDataTraitTestAccessor())->callFormatLogDetails($detailString, $substitutes));
    }

    #[DataProvider('formatLogDetailsNewFormatStringsDataProvider')]
    #[Test]
    public function formatLogDetailsNewFormatStringsWithSerializedSubstitutes(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, (new LogDataTraitTestAccessor())->callFormatLogDetails($detailString, serialize($substitutes)));
    }

    #[DataProvider('formatLogDetailsNewFormatStringsDataProvider')]
    #[Test]
    public function formatLogDetailsNewFormatStringsWithJsonEncodedSubstitutes(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, (new LogDataTraitTestAccessor())->callFormatLogDetails($detailString, json_encode($substitutes)));
    }

    public static function formatLogDetailsMixedFormatStringsDataProvider(): \Generator
    {
        yield 'Mixed empty arguments % %s {aPlaceholder}' => [
            'detailString' => 'Mixed empty arguments % %s {aPlaceholder}',
            'substitutes' => [],
            'expectedResult' => 'Mixed empty arguments %  {aPlaceholder}',
        ];
        yield 'Mixed non existing arguments % %s {aPlaceholder}' => [
            'detailString' => 'Mixed non existing arguments % %s {aPlaceholder}',
            'substitutes' => ['non-existing-argument' => 'non-existing-argument'],
            'expectedResult' => 'Mixed non existing arguments  {aPlaceholder}',
        ];
        yield 'Mixed Single argument % %s {myPlaceholder}' => [
            'detailString' => 'Mixed Single argument % %s {myPlaceholder}',
            'substitutes' => ['myPlaceholder' => 'replacedPlacerHolder'],
            'expectedResult' => 'Mixed Single argument  replacedPlacerHolder',
        ];
        yield 'Mixed multiple argument % %s {myPlaceholder} {anotherPlaceholder}' => [
            'detailString' => 'Mixed multiple argument % %s {myPlaceholder} {anotherPlaceholder}',
            'substitutes' => ['myPlaceholder' => 'replacedPlacerHolder', 'anotherPlaceholder' => 'replacedAnotherPlaceholder'],
            'expectedResult' => 'Mixed multiple argument  replacedPlacerHolder replacedAnotherPlaceholder',
        ];
        yield 'Mixed multiple argument % %s {myPlaceholder} {anotherPlaceholder} to many arguments' => [
            'detailString' => 'Mixed multiple argument % %s {myPlaceholder} {anotherPlaceholder} to many arguments',
            'substitutes' => ['non-existing-argument' => 'non-existing-argument', 'myPlaceholder' => 'replacedPlacerHolder', 'anotherPlaceholder' => 'replacedAnotherPlaceholder'],
            'expectedResult' => 'Mixed multiple argument  replacedPlacerHolder replacedAnotherPlaceholder to many arguments',
        ];
    }

    #[DataProvider('formatLogDetailsMixedFormatStringsDataProvider')]
    #[Test]
    public function formatLogDetailsStaticMixedFormatStrings(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, LogDataTraitTestAccessor::callFormatLogDetailsStatic($detailString, $substitutes));
    }

    #[DataProvider('formatLogDetailsMixedFormatStringsDataProvider')]
    #[Test]
    public function formatLogDetailsMixedFormatStrings(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, (new LogDataTraitTestAccessor())->callFormatLogDetails($detailString, $substitutes));
    }

    #[DataProvider('formatLogDetailsMixedFormatStringsDataProvider')]
    #[Test]
    public function formatLogDetailsMixedFormatStringsWithSerializedSubstitutes(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, (new LogDataTraitTestAccessor())->callFormatLogDetails($detailString, serialize($substitutes)));
    }

    #[DataProvider('formatLogDetailsMixedFormatStringsDataProvider')]
    #[Test]
    public function formatLogDetailsMixedFormatStringsWithJsonEncodedSubstitutes(string $detailString, array $substitutes, string $expectedResult): void
    {
        self::assertSame($expectedResult, (new LogDataTraitTestAccessor())->callFormatLogDetails($detailString, json_encode($substitutes)));
    }
}
