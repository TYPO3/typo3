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

namespace TYPO3\CMS\Core\Tests\Unit\Security;

use TYPO3\CMS\Core\Security\Nonce;
use TYPO3\CMS\Core\Security\NoncePool;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class NoncePoolTest extends UnitTestCase
{
    /**
     * @test
     */
    public function instantiationReflectsState(): void
    {
        $items = self::createItems();
        $validItems = array_slice($items, 0, 3);
        $pool = new NoncePool($items);

        foreach ($validItems as $name => $validItem) {
            self::assertSame($validItem, $pool->findSigningSecret($name));
        }
        self::assertSame(['rejected-name', 'revoked-a', 'revoked-b', 'revoked-c'], $pool->getRevocableNames());
        self::assertSame([], $pool->getEmittableNonces());
    }

    /**
     * @test
     */
    public function itemsAreMerged(): void
    {
        $itemsA = self::createItems();
        $itemsB = self::createItems();
        $validItems = array_merge(
            array_slice($itemsA, 0, 3),
            array_slice($itemsB, 0, 3)
        );
        $poolA = new NoncePool($itemsA);
        $poolB = new NoncePool($itemsB);
        $poolA->merge($poolB);

        foreach ($validItems as $name => $validItem) {
            self::assertSame($validItem, $poolA->findSigningSecret($name));
        }
        self::assertSame(['rejected-name', 'revoked-a', 'revoked-b', 'revoked-c'], $poolA->getRevocableNames());
        self::assertSame([], $poolA->getEmittableNonces());
    }

    /**
     * @test
     */
    public function provideSigningSecretDoesNotUseReceivedNonce(): void
    {
        $items = self::createItems();
        $pool = new NoncePool($items);
        $nonceA = $pool->provideSigningSecret();
        $nonceB = $pool->provideSigningSecret();
        self::assertSame($nonceA, $nonceB);
        self::assertNotContains($nonceA, $items);
    }

    public static function itemsArePurgedDataProvider(): \Generator
    {
        $items = self::createItems();
        $validItems = array_slice($items, 0, 3);
        yield [
            ['size' => 1],
            $items,
            $validItems,
            self::getArrayKeysDiff($items, array_slice($items, 0, 1)),
        ];
        yield [
            ['size' => 2],
            $items,
            $validItems,
            self::getArrayKeysDiff($items, array_slice($items, 0, 2)),
        ];
        yield [
            ['size' => 10],
            $items,
            $validItems,
            self::getArrayKeysDiff($items, $validItems),
        ];
    }

    /**
     * @test
     * @dataProvider itemsArePurgedDataProvider
     */
    public function itemsArePurged(array $options, array $items, array $validItems, array $revocableNames): void
    {
        $pool = (new NoncePool($items, $options))->purge();
        foreach ($validItems as $name => $validItem) {
            self::assertSame($validItem, $pool->findSigningSecret($name));
        }
        self::assertEmpty(array_diff($revocableNames, $pool->getRevocableNames()));
    }

    private static function createItems(): array
    {
        $nonceA = Nonce::create();
        $nonceB = Nonce::create();
        $nonceC = Nonce::create();
        return [
            $nonceA->getSigningIdentifier()->name => $nonceA,
            $nonceB->getSigningIdentifier()->name => $nonceB,
            $nonceC->getSigningIdentifier()->name => $nonceC,
            'rejected-name' => Nonce::create(),
            'revoked-a' => null,
            'revoked-b' => null,
            'revoked-c' => null,
        ];
    }

    private static function getArrayKeysDiff(array $items, array $without): array
    {
        $diff = array_diff_key($items, $without);
        return array_keys($diff);
    }
}
