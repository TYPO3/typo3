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

use TYPO3\CMS\Frontend\Imaging\GifBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @covers \TYPO3\CMS\Frontend\Imaging\GifBuilder
 */
class GifBuilderTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    private GifBuilder $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new GifBuilder();
    }

    /**
     * @return array<string, array{0: non-empty-string}>
     */
    public function singleIntegerDataProvider(): array
    {
        return [
            'positive integer' => ['1'],
            'negative integer' => ['-1'],
            'zero' => ['0'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider singleIntegerDataProvider
     */
    public function calcOffsetWithSingleIntegerReturnsTheGivenIntegerAsString(string $number): void
    {
        $result = $this->subject->calcOffset($number);

        self::assertSame($number, $result);
    }

    /**
     * @test
     */
    public function calcOffsetWithMultipleIntegersReturnsTheGivenIntegerCommaSeparated(): void
    {
        $numbers = '1,2,3';
        $result = $this->subject->calcOffset($numbers);

        self::assertSame($numbers, $result);
    }

    /**
     * @test
     */
    public function calcOffsetTrimsWhitespaceAroundProvidedNumbers(): void
    {
        $result = $this->subject->calcOffset(' 1, 2, 3 ');

        self::assertSame('1,2,3', $result);
    }

    /**
     * @return array<string, array{0: non-empty-string, 1: non-empty-string}>
     */
    public function roundingDataProvider(): array
    {
        return [
            'rounding down' => ['1.1', '1'],
            'rounding up' => ['1.9', '2'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider roundingDataProvider
     */
    public function calcOffsetRoundsNumbersToNearestInteger(string $input, string $expectedResult): void
    {
        $result = $this->subject->calcOffset($input);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{0: non-empty-string, 1: non-empty-string}>
     */
    public function calculationDataProvider(): array
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

    /**
     * @test
     *
     * @dataProvider calculationDataProvider
     */
    public function calcOffsetDoesTheProvidedCalculation(string $input, string $expectedResult): void
    {
        $result = $this->subject->calcOffset($input);

        self::assertSame($expectedResult, $result);
    }
}
