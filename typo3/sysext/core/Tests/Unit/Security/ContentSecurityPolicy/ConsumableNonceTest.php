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

namespace TYPO3\CMS\Core\Tests\Unit\Security\ContentSecurityPolicy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ConsumableNonceTest extends UnitTestCase
{
    public static function consumptionIsRecognizedDataProvider(): \Generator
    {
        yield 'unconsumed' => [0];
        yield 'consumed once' => [1];
        yield 'consumed twice' => [2];
    }

    #[DataProvider('consumptionIsRecognizedDataProvider')]
    #[Test]
    public function consumptionIsRecognized(int $consumption): void
    {
        $subject = new ConsumableNonce();
        for ($i = 0; $i < $consumption; $i++) {
            $subject->consumeInline();
        }
        self::assertCount($consumption, $subject);
    }

    #[Test]
    public function usesExistingValue(): void
    {
        $value = str_repeat('a', 40);
        $subject = new ConsumableNonce($value);
        self::assertSame($value, $subject->value);
        self::assertSame($value, $subject->consumeInline());
    }

    #[Test]
    public function consumptionAndReservationAreRecognized(): void
    {
        $subject = new ConsumableNonce();
        $subject->consumeInline();
        $subject->consumeStatic();
        $subject->consumeInline();
        $subject->consumeStatic();
        $subject->consumeInline();
        self::assertCount(5, $subject);
        self::assertSame(3, $subject->countInline());
        self::assertSame(2, $subject->countStatic());
    }
}
