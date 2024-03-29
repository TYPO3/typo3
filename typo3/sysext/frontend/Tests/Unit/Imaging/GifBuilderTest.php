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

namespace TYPO3\CMS\Frontend\Tests\Unit\Imaging;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GifBuilderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private GifBuilder $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new GifBuilder();
    }

    /**
     * @return array<string, array{0: non-empty-string}>
     */
    public static function singleIntegerDataProvider(): array
    {
        return [
            'positive integer' => ['1'],
            'negative integer' => ['-1'],
            'zero' => ['0'],
        ];
    }

    #[DataProvider('singleIntegerDataProvider')]
    #[Test]
    public function calcOffsetWithSingleIntegerReturnsTheGivenIntegerAsString(string $number): void
    {
        $result = $this->subject->calcOffset($number);

        self::assertSame($number, $result);
    }

    #[Test]
    public function calcOffsetWithMultipleIntegersReturnsTheGivenIntegerCommaSeparated(): void
    {
        $numbers = '1,2,3';
        $result = $this->subject->calcOffset($numbers);

        self::assertSame($numbers, $result);
    }

    #[Test]
    public function calcOffsetTrimsWhitespaceAroundProvidedNumbers(): void
    {
        $result = $this->subject->calcOffset(' 1, 2, 3 ');

        self::assertSame('1,2,3', $result);
    }

    /**
     * @return array<string, array{0: non-empty-string, 1: non-empty-string}>
     */
    public static function roundingDataProvider(): array
    {
        return [
            'rounding down' => ['1.1', '1'],
            'rounding up' => ['1.9', '2'],
        ];
    }

    #[DataProvider('roundingDataProvider')]
    #[Test]
    public function calcOffsetRoundsNumbersToNearestInteger(string $input, string $expectedResult): void
    {
        $result = $this->subject->calcOffset($input);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{0: non-empty-string, 1: non-empty-string}>
     */
    public static function calculationDataProvider(): array
    {
        return [
            'addition of positive numbers' => ['1+1', '2'],
            'addition of negative numbers' => ['-1+-1', '-2'],
            'subtraction' => ['5-2', '3'],
            'multiplication' => ['2*5', '10'],
            'division with whole-number result' => ['10/5', '2'],
            'division with rounding up' => ['19/5', '4'],
            'division with rounding down' => ['21/5', '4'],
            'modulo' => ['21%5', '1'],
        ];
    }

    #[DataProvider('calculationDataProvider')]
    #[Test]
    public function calcOffsetDoesTheProvidedCalculation(string $input, string $expectedResult): void
    {
        $result = $this->subject->calcOffset($input);

        self::assertSame($expectedResult, $result);
    }
}
