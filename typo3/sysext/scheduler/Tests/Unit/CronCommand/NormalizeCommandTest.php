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

namespace TYPO3\CMS\Scheduler\Tests\Unit\CronCommand;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand;
use TYPO3\CMS\Scheduler\Tests\Unit\CronCommand\AccessibleProxies\NormalizeCommandAccessibleProxy;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class NormalizeCommandTest extends UnitTestCase
{
    public static function normalizeValidDataProvider(): array
    {
        return [
            '@weekly' => ['@weekly', '0 0 * * 7'],
            ' @weekly ' => [' @weekly ', '0 0 * * 7'],
            '* * * * *' => ['* * * * *', '* * * * *'],
            '30 4 1,15 * 5' => ['30 4 1,15 * 5', '30 4 1,15 * 5'],
            '5 0 * * *' => ['5 0 * * *', '5 0 * * *'],
            '15 14 1 * *' => ['15 14 1 * *', '15 14 1 * *'],
            '0 22 * * 1-5' => ['0 22 * * 1-5', '0 22 * * 1,2,3,4,5'],
            '23 0-23/2 * * *' => ['23 0-23/2 * * *', '23 0,2,4,6,8,10,12,14,16,18,20,22 * * *'],
            '5 4 * * sun' => ['5 4 * * sun', '5 4 * * 7'],
            '0-3/2,7 0,4 20-22, feb,mar-jun/2,7 1-3,sun' => [
                '0-3/2,7 0,4 20-22 feb,mar-jun/2,7 1-3,sun',
                '0,2,7 0,4 20,21,22 2,3,5,7 1,2,3,7',
            ],
            '0-20/10 * * * *' => ['0-20/10 * * * *', '0,10,20 * * * *'],
            '* * 2 * *' => ['* * 2 * *', '* * 2 * *'],
            '* * 2,7 * *' => ['* * 2,7 * *', '* * 2,7 * *'],
            '* * 2-4,10 * *' => ['* * 2-4,10 * *', '* * 2,3,4,10 * *'],
            '* * */14 * *' => ['* * */14 * *', '* * 1,15,29 * *'],
            '* * 2,4-6/2,*/14 * *' => ['* * 2,4-6/2,*/14 * *', '* * 1,2,4,6,15,29 * *'],
            '* * * * 1' => ['* * * * 1', '* * * * 1'],
            '0 0 * * 0' => ['0 0 * * 0', '0 0 * * 7'],
            '0 0 * * 7' => ['0 0 * * 7', '0 0 * * 7'],
            '* * 1,2 * 1' => ['* * 1,2 * 1', '* * 1,2 * 1'],
            '15 02 * * *' => ['15 02 * * *', '15 2 * * *'],
            '08 02 * * *' => ['08 02 * * *', '8 2 * * *'],
            '15 00 * * *' => ['15 00 * * *', '15 0 * * *'],
        ];
    }

    #[DataProvider('normalizeValidDataProvider')]
    #[Test]
    public function normalizeConvertsCronCommand(string $expression, string $expected): void
    {
        $result = NormalizeCommand::normalize($expression);
        self::assertEquals($expected, $result);
    }

    public static function validSpecialKeywordsDataProvider(): array
    {
        return [
            '@yearly' => ['@yearly', '0 0 1 1 *'],
            '@annually' => ['@annually', '0 0 1 1 *'],
            '@monthly' => ['@monthly', '0 0 1 * *'],
            '@weekly' => ['@weekly', '0 0 * * 0'],
            '@daily' => ['@daily', '0 0 * * *'],
            '@midnight' => ['@midnight', '0 0 * * *'],
            '@hourly' => ['@hourly', '0 * * * *'],
        ];
    }

    #[DataProvider('validSpecialKeywordsDataProvider')]
    #[Test]
    public function convertKeywordsToCronCommandConvertsValidKeywords(string $keyword, string $expectedCronCommand): void
    {
        $result = NormalizeCommandAccessibleProxy::convertKeywordsToCronCommand($keyword);
        self::assertEquals($expectedCronCommand, $result);
    }

    #[Test]
    public function convertKeywordsToCronCommandReturnsUnchangedCommandIfKeywordWasNotFound(): void
    {
        $invalidKeyword = 'foo';
        $result = NormalizeCommandAccessibleProxy::convertKeywordsToCronCommand($invalidKeyword);
        self::assertEquals($invalidKeyword, $result);
    }

    public static function normalizeFieldsValidDataProvider(): array
    {
        return [
            '1-2 * * * *' => ['1-2 * * * *', '1,2 * * * *'],
            '* 1-2 * * *' => ['* 1-2 * * *', '* 1,2 * * *'],
            '* * 1-2 * *' => ['* * 1-2 * *', '* * 1,2 * *'],
            '* * * 1-2 *' => ['* * * 1-2 *', '* * * 1,2 *'],
            '* * * * 1-2' => ['* * * * 1-2', '* * * * 1,2'],
        ];
    }

    #[DataProvider('normalizeFieldsValidDataProvider')]
    #[Test]
    public function normalizeFieldsConvertsField(string $expression, string $expected): void
    {
        $result = NormalizeCommandAccessibleProxy::normalizeFields($expression);
        self::assertEquals($expected, $result);
    }

    public static function normalizeMonthAndWeekdayFieldValidDataProvider(): array
    {
        return [
            '* monthField' => ['*', true, '*'],
            'string 1 monthField' => ['1', true, '1'],
            'jan' => ['jan', true, '1'],
            'feb/2' => ['feb/2', true, '2'],
            'jan-feb/2' => ['jan-feb/2', true, '1'],
            '1-2 monthField' => ['1-2', true, '1,2'],
            '1-3/2,feb,may,6' => ['1-3/2,feb,may,6', true, '1,2,3,5,6'],
            '*/4' => ['*/4', true, '1,5,9'],
            '* !monthField' => ['*', false, '*'],
            'string 1, !monthField' => ['1', false, '1'],
            'fri' => ['fri', false, '5'],
            'sun' => ['sun', false, '7'],
            'string 0 for sunday' => ['0', false, '7'],
            '0,1' => ['0,1', false, '1,7'],
            '*/3' => ['*/3', false, '1,4,7'],
            'tue/2' => ['tue/2', false, '2'],
            '1-2 !monthField' => ['1-2', false, '1,2'],
            'tue-fri/2' => ['tue-fri/2', false, '2,4'],
            '1-3/2,tue,fri,6' => ['1-3/2,tue,fri,6', false, '1,2,3,5,6'],
        ];
    }

    #[DataProvider('normalizeMonthAndWeekdayFieldValidDataProvider')]
    #[Test]
    public function normalizeMonthAndWeekdayFieldReturnsNormalizedListForValidExpression(
        string $expression,
        bool $isMonthField,
        string $expected
    ): void {
        $result = NormalizeCommandAccessibleProxy::normalizeMonthAndWeekdayField($expression, $isMonthField);
        self::assertSame($expected, $result);
    }

    public static function normalizeMonthAndWeekdayFieldInvalidDataProvider(): array
    {
        return [
            'mon' => ['mon', true, 1291083486],
            '1-2/mon' => ['1-2/mon', true, 1291414957],
            '0,1' => ['0,1', true, 1291083486],
            'feb' => ['feb', false, 1291163589],
            '1-2/feb' => ['1-2/feb', false, 1291414957],
            '0-fri/2,7' => ['0-fri/2,7', false, 1291237145],
        ];
    }

    #[DataProvider('normalizeMonthAndWeekdayFieldInvalidDataProvider')]
    #[Test]
    public function normalizeMonthAndWeekdayFieldThrowsExceptionForInvalidExpression(
        string $expression,
        bool $isMonthField,
        int $expectedExceptionCode
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedExceptionCode);
        NormalizeCommandAccessibleProxy::normalizeMonthAndWeekdayField($expression, $isMonthField);
    }

    public static function normalizeIntegerFieldValidDataProvider(): array
    {
        return [
            '*' => ['*', '*'],
            'string 2' => ['2', '2'],
            'integer 3' => [3, '3'],
            'list of values' => ['1,2,3', '1,2,3'],
            'unsorted list of values' => ['3,1,5', '1,3,5'],
            'duplicate values' => ['0-2/2,2', '0,2'],
            'additional field between steps' => ['1-3/2,2', '1,2,3'],
            '2-4' => ['2-4', '2,3,4'],
            'simple step 4/4' => ['4/4', '4'],
            'step 2-7/5' => ['2-7/5', '2,7'],
            'steps 4-12/4' => ['4-12/4', '4,8,12'],
            '0-59/20' => ['0-59/20', '0,20,40'],
            '*/20' => ['*/20', '0,20,40'],
        ];
    }

    #[DataProvider('normalizeIntegerFieldValidDataProvider')]
    #[Test]
    public function normalizeIntegerFieldReturnsNormalizedListForValidExpression(string|int $expression, string $expected): void
    {
        $result = NormalizeCommandAccessibleProxy::normalizeIntegerField($expression);
        self::assertSame($expected, $result);
    }

    public static function normalizeIntegerFieldInvalidDataProvider(): array
    {
        return [
            'string foo' => ['foo', 0, 59, 1291429389],
            'empty string' => ['', 0, 59, 1291429389],
            '4-3' => ['4-3', 0, 59, 1291237145],
            '/2' => ['/2', 0, 59, 1291234985],
            '/' => ['/', 0, 59, 1291234985],
            'left bound too low' => ['2-4', 3, 4, 1291470084],
            'right bound too high' => ['2-4', 2, 3, 1291470170],
            'left and right bound' => ['2-5', 2, 4, 1291470170],
            'element in list is lower than allowed' => ['2,1,4', 2, 4, 1291470084],
            'element in list is higher than allowed' => ['2,5,4', 1, 4, 1291470170],
        ];
    }

    #[DataProvider('normalizeIntegerFieldInvalidDataProvider')]
    #[Test]
    public function normalizeIntegerFieldThrowsExceptionForInvalidExpressions(
        string $expression,
        int $lowerBound,
        int $upperBound,
        int $expectedExceptionCode
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedExceptionCode);
        NormalizeCommandAccessibleProxy::normalizeIntegerField($expression, $lowerBound, $upperBound);
    }

    #[Test]
    public function splitFieldsReturnsIntegerArrayWithFieldsSplitByWhitespace(): void
    {
        $result = NormalizeCommandAccessibleProxy::splitFields('12,13 * 1-12/2,14 jan fri');
        $expectedResult = [
            0 => '12,13',
            1 => '*',
            2 => '1-12/2,14',
            3 => 'jan',
            4 => 'fri',
        ];
        self::assertSame($expectedResult, $result);
    }

    public static function invalidCronCommandFieldsDataProvider(): array
    {
        return [
            'empty string' => [''],
            'foo' => ['foo'],
            'integer 4' => [4],
            'four fields' => ['* * * *'],
            'six fields' => ['* * * * * *'],
        ];
    }

    #[DataProvider('invalidCronCommandFieldsDataProvider')]
    #[Test]
    public function splitFieldsThrowsExceptionIfCronCommandDoesNotContainFiveFields(string|int $cronCommand): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1291227373);
        NormalizeCommandAccessibleProxy::splitFields($cronCommand);
    }

    public static function validRangeDataProvider(): array
    {
        return [
            'single value' => ['3', '3'],
            'integer 3' => [3, '3'],
            '0-0' => ['0-0', '0'],
            '4-4' => ['4-4', '4'],
            '0-3' => ['0-3', '0,1,2,3'],
            '4-5' => ['4-5', '4,5'],
        ];
    }

    #[DataProvider('validRangeDataProvider')]
    #[Test]
    public function convertRangeToListOfValuesReturnsCorrectListForValidRanges(string|int $range, string $expected): void
    {
        $result = NormalizeCommandAccessibleProxy::convertRangeToListOfValues($range);
        self::assertSame($expected, $result);
    }

    public static function invalidRangeDataProvider(): array
    {
        return [
            'empty string' => ['', 1291234985],
            'string' => ['foo', 1291237668],
            'single dash' => ['-', 1291237668],
            'left part is string' => ['foo-5', 1291237668],
            'right part is string' => ['5-foo', 1291237668],
            'range of strings' => ['foo-bar', 1291237668],
            'string five minus' => ['5-', 1291237668],
            'string minus five' => ['-5', 1291237668],
            'more than one dash' => ['2-3-4', 1291234986],
            'left part bigger than right part' => ['6-3', 1291237145],
        ];
    }

    #[DataProvider('invalidRangeDataProvider')]
    #[Test]
    public function convertRangeToListOfValuesThrowsExceptionForInvalidRanges(string $range, int $expectedExceptionCode): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedExceptionCode);
        NormalizeCommandAccessibleProxy::convertRangeToListOfValues($range);
    }

    public static function validStepsDataProvider(): array
    {
        return [
            '2/2' => ['2/2', '2'],
            '2,3,4/2' => ['2,3,4/2', '2,4'],
            '1,2,3,4,5,6,7/3' => ['1,2,3,4,5,6,7/3', '1,4,7'],
            '0,1,2,3,4,5,6/3' => ['0,1,2,3,4,5,6/3', '0,3,6'],
        ];
    }

    #[DataProvider('validStepsDataProvider')]
    #[Test]
    public function reduceListOfValuesByStepValueReturnsCorrectListOfValues(string $stepExpression, string $expected): void
    {
        $result = NormalizeCommandAccessibleProxy::reduceListOfValuesByStepValue($stepExpression);
        self::assertSame($expected, $result);
    }

    public static function invalidStepsDataProvider(): array
    {
        return [
            'empty string' => ['', 1291234987],
            'slash only' => ['/', 1291414955],
            'left part empty' => ['/2', 1291414955],
            'right part empty' => ['2/', 1291414956],
            'multiples slashes' => ['1/2/3', 1291242168],
            '2-2' => ['2-2', 1291414956],
            '2.3/2' => ['2.3/2', 1291414958],
            '2,3,4/2.3' => ['2,3,4/2.3', 1291414957],
            '2,3,4/2,3' => ['2,3,4/2,3', 1291414957],
        ];
    }

    #[DataProvider('invalidStepsDataProvider')]
    #[Test]
    public function reduceListOfValuesByStepValueThrowsExceptionForInvalidStepExpressions(
        string $stepExpression,
        int $expectedExceptionCode
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedExceptionCode);
        NormalizeCommandAccessibleProxy::reduceListOfValuesByStepValue($stepExpression);
    }

    #[Test]
    public function normalizeMonthAndWeekdayNormalizesAMonth(): void
    {
        $result = NormalizeCommandAccessibleProxy::normalizeMonthAndWeekday('feb', true);
        self::assertSame('2', $result);
    }

    #[Test]
    public function normalizeMonthAndWeekdayNormalizesAWeekday(): void
    {
        $result = NormalizeCommandAccessibleProxy::normalizeMonthAndWeekday('fri', false);
        self::assertSame('5', $result);
    }

    #[Test]
    public function normalizeMonthAndWeekdayLeavesValueUnchanged(): void
    {
        $result = NormalizeCommandAccessibleProxy::normalizeMonthAndWeekday('2');
        self::assertSame('2', $result);
    }

    public static function validMonthNamesDataProvider(): array
    {
        return [
            'jan' => ['jan', 1],
            'feb' => ['feb', 2],
            'MaR' => ['MaR', 3],
            'aPr' => ['aPr', 4],
            'MAY' => ['MAY', 5],
            'jun' => ['jun', 6],
            'jul' => ['jul', 7],
            'aug' => ['aug', 8],
            'sep' => ['sep', 9],
            'oct' => ['oct', 10],
            'nov' => ['nov', 11],
            'dec' => ['dec', 12],
            'string 7' => ['7', 7],
            'integer 7' => [7, 7],
            'string 07' => ['07', 7],
            'integer 07' => [7, 7],
        ];
    }

    #[DataProvider('validMonthNamesDataProvider')]
    #[Test]
    public function normalizeMonthConvertsName(string|int $monthName, int $expectedInteger): void
    {
        $result = NormalizeCommandAccessibleProxy::normalizeMonth($monthName);
        self::assertSame($expectedInteger, $result);
    }

    public static function invalidMonthNamesDataProvider(): array
    {
        return [
            'sep-' => ['sep-', 1291083486],
            '-September-' => ['-September-', 1291083486],
            ',sep' => [',sep', 1291083486],
            ',September,' => [',September,', 1291083486],
            'sep/' => ['sep/', 1291083486],
            '/sep' => ['/sep', 1291083486],
            '/September/' => ['/September/', 1291083486],
            'foo' => ['foo', 1291083486],
            'Tuesday' => ['Tuesday', 1291083486],
            'Tue' => ['Tue', 1291083486],
            'string 0' => ['0', 1291083486],
            'integer 0' => [0, 1291083486],
            'string seven' => ['seven', 1291083486],
            'string 13' => ['13', 1291083486],
            'integer 13' => [13, 1291083486],
            'integer 99' => [99, 1291083486],
            'integer 2010' => [2010, 1291083486],
            'string minus 7' => ['-7', 1291083486],
            'negative integer 7' => [-7, 1291083486],
        ];
    }

    #[DataProvider('invalidMonthNamesDataProvider')]
    #[Test]
    public function normalizeMonthThrowsExceptionForInvalidMonthRepresentation(
        string|int $invalidMonthName,
        int $expectedExceptionCode
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedExceptionCode);
        NormalizeCommandAccessibleProxy::normalizeMonth($invalidMonthName);
    }

    public static function validWeekdayDataProvider(): array
    {
        return [
            'string 1' => ['1', 1],
            'string 2' => ['2', 2],
            'string 02' => ['02', 2],
            'integer 02' => [2, 2],
            'string 3' => ['3', 3],
            'string 4' => ['4', 4],
            'string 5' => ['5', 5],
            'integer 5' => [5, 5],
            'string 6' => ['6', 6],
            'string 7' => ['7', 7],
            'string 0' => ['0', 7],
            'integer 0' => [0, 7],
            'mon' => ['mon', 1],
            'monday' => ['monday', 1],
            'tue' => ['tue', 2],
            'tuesday' => ['tuesday', 2],
            'WED' => ['WED', 3],
            'WEDnesday' => ['WEDnesday', 3],
            'tHu' => ['tHu', 4],
            'Thursday' => ['Thursday', 4],
            'fri' => ['fri', 5],
            'friday' => ['friday', 5],
            'sat' => ['sat', 6],
            'saturday' => ['saturday', 6],
            'sun' => ['sun', 7],
            'sunday' => ['sunday', 7],
        ];
    }

    #[DataProvider('validWeekdayDataProvider')]
    #[Test]
    public function normalizeWeekdayConvertsName(string|int $weekday, int $expectedInteger): void
    {
        $result = NormalizeCommandAccessibleProxy::normalizeWeekday($weekday);
        self::assertSame($expectedInteger, $result);
    }

    public static function invalidWeekdayDataProvider(): array
    {
        return [
            '-fri' => ['-fri'],
            'fri-' => ['fri-'],
            '-friday-' => ['-friday-'],
            '/fri' => ['/fri'],
            'fri/' => ['fri/'],
            '/friday/' => ['/friday/'],
            ',fri' => [',fri'],
            ',friday,' => [',friday,'],
            'string minus 1' => ['-1'],
            'integer -1' => [-1],
            'string seven' => ['seven'],
            'string 8' => ['8'],
            'string 29' => ['29'],
            'string 2010' => ['2010'],
            'Jan' => ['Jan'],
            'January' => ['January'],
            'MARCH' => ['MARCH'],
        ];
    }

    #[DataProvider('invalidWeekdayDataProvider')]
    #[Test]
    public function normalizeWeekdayThrowsExceptionForInvalidWeekdayRepresentation(string|int $weekday): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1291163589);
        NormalizeCommandAccessibleProxy::normalizeWeekday($weekday);
    }
}
