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

namespace TYPO3\CMS\Redirects\Tests\Unit\Cache;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Redirects\Cache\BloomFilter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BloomFilterTest extends UnitTestCase
{
    #[Test]
    public function emptyFilterContainsNothing(): void
    {
        $subject = BloomFilter::create(0);
        self::assertFalse($subject->mightContain(''));
        self::assertFalse($subject->mightContain('/foo'));
    }

    #[Test]
    public function addedKeysAreAlwaysContained(): void
    {
        $subject = BloomFilter::create(10000);
        for ($i = 0; $i < 10000; $i++) {
            $subject->add('/path-' . $i);
        }
        for ($i = 0; $i < 10000; $i++) {
            self::assertTrue($subject->mightContain('/path-' . $i));
        }
    }

    #[Test]
    public function keysAreComparedExactly(): void
    {
        $subject = BloomFilter::create(3);
        $subject->add('/Foo');
        $subject->add('');
        self::assertTrue($subject->mightContain('/Foo'));
        self::assertTrue($subject->mightContain(''));
        self::assertFalse($subject->mightContain('/foo'));
        self::assertFalse($subject->mightContain('/Foo '));
    }

    #[Test]
    public function falsePositiveRateIsNotExceededSignificantly(): void
    {
        $subject = BloomFilter::create(10000, 0.01);
        for ($i = 0; $i < 10000; $i++) {
            $subject->add('/path-' . $i);
        }
        $falsePositives = 0;
        for ($i = 0; $i < 10000; $i++) {
            if ($subject->mightContain('/other-' . $i)) {
                $falsePositives++;
            }
        }
        self::assertLessThan(200, $falsePositives);
    }

    #[Test]
    public function exportedFilterCanBeRestored(): void
    {
        $subject = BloomFilter::create(2);
        $subject->add('/foo');
        $restored = BloomFilter::fromArray(unserialize(serialize($subject->toArray()), ['allowed_classes' => false]));
        self::assertInstanceOf(BloomFilter::class, $restored);
        self::assertTrue($restored->mightContain('/foo'));
        self::assertFalse($restored->mightContain('/bar'));
    }

    public static function invalidDataIsRejectedDataProvider(): \Generator
    {
        yield 'empty' => [[]];
        yield 'bits missing' => [['bitCount' => 64, 'hashCount' => 7]];
        yield 'bits too short' => [['bits' => str_repeat("\0", 7), 'bitCount' => 64, 'hashCount' => 7]];
        yield 'bit count too small' => [['bits' => '', 'bitCount' => 0, 'hashCount' => 7]];
        yield 'hash count zero' => [['bits' => str_repeat("\0", 8), 'bitCount' => 64, 'hashCount' => 0]];
        yield 'bit count as string' => [['bits' => str_repeat("\0", 8), 'bitCount' => '64', 'hashCount' => 7]];
    }

    #[DataProvider('invalidDataIsRejectedDataProvider')]
    #[Test]
    public function invalidDataIsRejected(array $data): void
    {
        self::assertNull(BloomFilter::fromArray($data));
    }

    #[Test]
    public function invalidFalsePositiveRateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791470082);
        BloomFilter::create(10, 1.0);
    }
}
