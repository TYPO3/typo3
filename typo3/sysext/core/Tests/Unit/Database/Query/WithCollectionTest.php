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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Query;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\With;
use TYPO3\CMS\Core\Database\Query\WithCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class WithCollectionTest extends UnitTestCase
{
    public static function withPartsAreSortedRespectingDependencySettingDataProvider(): \Generator
    {
        $simpleWith = new With('simple', [], [], '', false);
        $secondWithDependingOnSimpleWith = new With('second', [], ['simple'], '', false);
        yield 'depending cte added before' => [
            'parts' => [
                $simpleWith,
                $secondWithDependingOnSimpleWith,
            ],
            'expectedParts' => [
                $simpleWith,
                $secondWithDependingOnSimpleWith,
            ],
        ];
        yield 'depending cte added after' => [
            'parts' => [
                $secondWithDependingOnSimpleWith,
                $simpleWith,
            ],
            'expectedParts' => [
                $simpleWith,
                $secondWithDependingOnSimpleWith,
            ],
        ];

        $firstWith = new With('first', [], [], '', false);
        $secondWith = new With('second', [], ['first'], '', false);
        $thirdWith = new With('third', [], ['second', 'first'], '', false);
        yield 'multi dependency' => [
            'parts' => [
                $thirdWith,
                $firstWith,
                $secondWith,
            ],
            'expectedParts' => [
                $firstWith,
                $secondWith,
                $thirdWith,
            ],
        ];
    }

    #[DataProvider('withPartsAreSortedRespectingDependencySettingDataProvider')]
    #[Test]
    public function withPartsAreSortedRespectingDependencySetting(array $parts, array $expectedParts): void
    {
        $reflectionClass = new \ReflectionClass(WithCollection::class);
        $withCollection = new WithCollection();
        $withCollection->set(...array_values($parts));
        self::assertSame($expectedParts, $reflectionClass->getMethod('getSortedParts')->invoke($withCollection));
    }

    #[Test]
    public function cycleWithPartsThrowsUnexpectedValueException(): void
    {
        $leftCycleWith = new With('left', [], ['right'], '', false);
        $rightCycleWith = new With('right', [], ['left'], '', false);
        $reflectionClass = new \ReflectionClass(WithCollection::class);
        $withCollection = new WithCollection();
        $withCollection->set($rightCycleWith, $leftCycleWith);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381960494);
        $this->expectExceptionMessageMatches('/^Your dependencies have cycles. That will not work out. Cycles found: /');

        $reflectionClass->getMethod('getSortedParts')->invoke($withCollection);
    }
}
