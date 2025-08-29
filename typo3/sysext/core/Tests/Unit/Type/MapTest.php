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

namespace TYPO3\CMS\Core\Tests\Unit\Type;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Type\Map;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MapTest extends UnitTestCase
{
    #[Test]
    public function mapIsArrayAccessible(): void
    {
        $aKey = new \stdClass();
        $aValue = new \stdClass();
        $bKey = new \stdClass();
        $bValue = new \stdClass();

        $map = new Map();
        $map[$aKey] = $aValue;
        $map[$bKey] = $bValue;

        self::assertInstanceOf(Map::class, $map);
        self::assertCount(2, $map);
        self::assertSame($aValue, $map[$aKey]);
        self::assertSame($bValue, $map[$bKey]);
    }

    #[Test]
    public function mapKeyCanBeUnset(): void
    {
        $aKey = new \stdClass();
        $aValue = new \stdClass();
        $bKey = new \stdClass();
        $bValue = new \stdClass();

        $map = new Map();
        $map[$aKey] = $aValue;
        $map[$bKey] = $bValue;

        unset($map[$bKey]);

        self::assertCount(1, $map);
        self::assertFalse(isset($map[$bKey]));
    }

    #[Test]
    public function mapCanBeIterated(): void
    {
        $aKey = new \stdClass();
        $aValue = new \stdClass();
        $bKey = new \stdClass();
        $bValue = new \stdClass();

        $map = new Map();
        $map[$aKey] = $aValue;
        $map[$bKey] = $bValue;

        $entries = [];
        foreach ($map as $key => $value) {
            $entries[] = [$key, $value];
        }

        $expectation = [
            [$aKey, $aValue],
            [$bKey, $bValue],
        ];
        self::assertSame($expectation, $entries);
    }

    #[Test]
    public function mapIsCreatedFromEntries(): void
    {
        $aKey = new \stdClass();
        $aValue = new \stdClass();
        $bKey = new \stdClass();
        $bValue = new \stdClass();

        $map = Map::fromEntries(
            [$aKey, $aValue],
            [$bKey, $bValue],
        );

        self::assertCount(2, $map);
        self::assertSame($aValue, $map[$aKey]);
        self::assertSame($bValue, $map[$bKey]);
    }

    #[Test]
    public function mapCanBeAssignedToOtherMap(): void
    {
        $aKey = new \stdClass();
        $aValue = new \stdClass();
        $bKey = new \stdClass();
        $bValue = new \stdClass();

        $map = new Map();
        $map[$aKey] = $aValue;
        $otherMap = new Map();
        $otherMap[$bKey] = $bValue;

        $otherMap->assign($map);

        self::assertCount(2, $otherMap);
        self::assertSame($bValue, $otherMap[$bKey]);
        self::assertSame($aValue, $otherMap[$aKey]);
    }

    #[Test]
    public function mapKeysAndValuesAreFetched(): void
    {
        $aKey = new \stdClass();
        $aValue = new \stdClass();
        $bKey = new \stdClass();
        $bValue = new \stdClass();

        $map = Map::fromEntries(
            [$aKey, $aValue],
            [$bKey, $bValue],
        );

        self::assertSame([$aKey, $bKey], $map->keys());
        self::assertSame([$aValue, $bValue], $map->values());
    }

    public static function mapNextManagesStateDataProvider(): \Generator
    {
        $aKey = new \stdClass();
        $aKey->value = 'aValue';
        $bKey = new \stdClass();
        $bKey->value = 'bValue';
        yield '=ø @ø ?ø' => ['keys' => [], 'index' => 0, 'expectedKey' => null, 'expectedValid' => false];
        yield '=a @a ?a' => ['keys' => [$aKey], 'index' => 0, 'expectedKey' => $aKey, 'expectedValid' => true];
        yield '=a @b! ?ø' => ['keys' => [$aKey], 'index' => 1, 'expectedKey' => null, 'expectedValid' => false];
        yield '=ab @a ?a' => ['keys' => [$aKey, $bKey], 'index' => 0, 'expectedKey' => $aKey, 'expectedValid' => true];
        yield '=ab @b ?b' => ['keys' => [$aKey, $bKey], 'index' => 1, 'expectedKey' => $bKey, 'expectedValid' => true];
        yield '=ab @b! ?ø' => ['keys' => [$aKey, $bKey], 'index' => 2, 'expectedKey' => null, 'expectedValid' => false];
    }

    #[Test]
    #[DataProvider('mapNextManagesStateDataProvider')]
    public function mapNextManagesState(array $keys, int $index, mixed $expectedKey, bool $expectedValid): void
    {
        $map = new Map();
        foreach ($keys as $key) {
            $map[$key] = $key->value;
        }
        for ($i = 0; $i < $index; $i++) {
            $map->next();
        }
        $expectedValue = $expectedKey?->value;
        self::assertSame($expectedValue, $map->current());
        self::assertSame($expectedKey, $map->key());
        self::assertSame($expectedValid, $map->valid());
    }

    public static function mapSetManagesStateDataProvider(): \Generator
    {
        $aKey = new \stdClass();
        $aKey->value = 'aValue';
        $bKey = new \stdClass();
        $bKey->value = 'bValue';
        $cKey = new \stdClass();
        $cKey->value = 'cValue';
        yield '=ø @ø +ab ?a' => ['keys' => [], 'index' => 0, 'modifyKeys' => [$aKey, $bKey], 'expectedKey' => $aKey, 'expectedValid' => true];
        yield '=ø @b! +ab ?a' => ['keys' => [], 'index' => 1, 'modifyKeys' => [$aKey, $bKey], 'expectedKey' => $aKey, 'expectedValid' => true];
        yield '=a @a +bc ?a' => ['keys' => [$aKey], 'index' => 0, 'modifyKeys' => [$bKey, $cKey], 'expectedKey' => $aKey, 'expectedValid' => true];
        yield '=a @b! +bc ?b' => ['keys' => [$aKey], 'index' => 1, 'modifyKeys' => [$bKey, $cKey], 'expectedKey' => $bKey, 'expectedValid' => true];
        yield '=ab @b +c ?b' => ['keys' => [$aKey, $bKey], 'index' => 1, 'modifyKeys' => [$cKey], 'expectedKey' => $bKey, 'expectedValid' => true];
        yield '=ab @c! +c ?c' => ['keys' => [$aKey, $bKey], 'index' => 2, 'modifyKeys' => [$cKey], 'expectedKey' => $cKey, 'expectedValid' => true];
        yield '=abc @c +c ?c' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 2, 'modifyKeys' => [$cKey], 'expectedKey' => $cKey, 'expectedValid' => true];
    }

    #[Test]
    #[DataProvider('mapSetManagesStateDataProvider')]
    public function mapSetManagesState(array $keys, int $index, array $modifyKeys, mixed $expectedKey, bool $expectedValid): void
    {
        $map = new Map();
        foreach ($keys as $key) {
            $map[$key] = $key->value;
        }
        for ($i = 0; $i < $index; $i++) {
            $map->next();
        }
        foreach ($modifyKeys as $modifyKey) {
            $map[$modifyKey] = $modifyKey->value . '!';
        }
        $expectedValue = $expectedKey?->value;
        if ($expectedValue !== null && in_array($expectedKey, $modifyKeys, true)) {
            $expectedValue .= '!';
        }
        self::assertSame($expectedValue, $map->current());
        self::assertSame($expectedKey, $map->key());
        self::assertSame($expectedValid, $map->valid());
    }

    public static function mapUnsetManagesStateDataProvider(): \Generator
    {
        // PHP 8.1 behaves different here
        // * having [0 => A, 1 => B, 2 => C]
        // * being a position 1 (B)
        // * removing the same offset 1 (B)
        // * PHP 8.1 moves the pointer to 0 (A), PHP 8.2+ moves the pointer to 2 (C, which is actually 1 now)
        $isAtLeastPhp82 = version_compare(PHP_VERSION, '8.2', '>=');

        $aKey = new \stdClass();
        $aKey->value = 'aValue';
        $bKey = new \stdClass();
        $bKey->value = 'bValue';
        $cKey = new \stdClass();
        $cKey->value = 'cValue';
        yield '=abc @a -a ?b' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 0, 'modifyKeys' => [$aKey], 'expectedKey' => $bKey, 'expectedValid' => true];
        yield '=abc @b -a ?b' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 1, 'modifyKeys' => [$aKey], 'expectedKey' => $bKey, 'expectedValid' => true];
        if ($isAtLeastPhp82) {
            yield '=abc @c -a ?c' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 2, 'modifyKeys' => [$aKey], 'expectedKey' => $cKey, 'expectedValid' => true];
            yield '=abc @c! -a ?c' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 2, 'modifyKeys' => [$aKey], 'expectedKey' => $cKey, 'expectedValid' => true];
        }
        yield '=abc @a -b ?a' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 0, 'modifyKeys' => [$bKey], 'expectedKey' => $aKey, 'expectedValid' => true];
        if ($isAtLeastPhp82) {
            yield '=abc @b -b ?c' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 1, 'modifyKeys' => [$bKey], 'expectedKey' => $cKey, 'expectedValid' => true];
            yield '=abc @c -b ?c' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 2, 'modifyKeys' => [$bKey], 'expectedKey' => $cKey, 'expectedValid' => true];
        }
        yield '=abc @a -c ?a' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 0, 'modifyKeys' => [$cKey], 'expectedKey' => $aKey, 'expectedValid' => true];
        if ($isAtLeastPhp82) {
            yield '=abc @b -c ?b' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 1, 'modifyKeys' => [$cKey], 'expectedKey' => $bKey, 'expectedValid' => true];
            yield '=abc @c -c ?ø' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 2, 'modifyKeys' => [$cKey], 'expectedKey' => null, 'expectedValid' => false];
        }
        yield '=abc @a -abc ?ø' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 0, 'modifyKeys' => [$aKey, $bKey, $cKey], 'expectedKey' => null, 'expectedValid' => false];
        yield '=ø @ø -abc ?ø' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 0, 'modifyKeys' => [$aKey, $bKey, $cKey], 'expectedKey' => null, 'expectedValid' => false];
    }

    #[Test]
    #[DataProvider('mapUnsetManagesStateDataProvider')]
    public function mapUnsetManagesState(array $keys, int $index, array $modifyKeys, mixed $expectedKey, bool $expectedValid): void
    {
        $map = new Map();
        foreach ($keys as $key) {
            $map[$key] = $key->value;
        }
        for ($i = 0; $i < $index; $i++) {
            $map->next();
        }
        foreach ($modifyKeys as $modifyKey) {
            unset($map[$modifyKey]);
        }
        $expectedValue = $expectedKey?->value;
        self::assertSame($expectedValue, $map->current());
        self::assertSame($expectedKey, $map->key());
        self::assertSame($expectedValid, $map->valid());
    }
}
