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
    public static function mapKeysDataProvider(): \Generator
    {
        yield 'string keys' => ['aKey', 'bKey'];
        yield 'array keys' => [['aArrayKey'], ['bArrayKey']];
        yield 'object keys' => [new \stdClass(), new \stdClass()];
        yield 'object & string keys' => [new \stdClass(), 'bKey'];
    }

    #[Test]
    #[DataProvider('mapKeysDataProvider')]
    public function mapIsArrayAccessible(mixed $aKey, mixed $bKey): void
    {
        $aValue = new \stdClass();
        $bValue = new \stdClass();

        $map = new Map();
        $map[$aKey] = $aValue;
        $map[$bKey] = $bValue;

        self::assertCount(2, $map);
        self::assertSame($aValue, $map[$aKey]);
        self::assertSame($bValue, $map[$bKey]);
    }

    #[Test]
    #[DataProvider('mapKeysDataProvider')]
    public function mapKeyCanBeUnset(mixed $aKey, mixed $bKey): void
    {
        $aValue = new \stdClass();
        $bValue = new \stdClass();

        $map = new Map();
        $map[$aKey] = $aValue;
        $map[$bKey] = $bValue;

        unset($map[$bKey]);

        self::assertCount(1, $map);
        self::assertFalse(isset($map[$bKey]));
    }

    #[Test]
    #[DataProvider('mapKeysDataProvider')]
    public function mapCanBeIterated(mixed $aKey, mixed $bKey): void
    {
        $aValue = new \stdClass();
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
    #[DataProvider('mapKeysDataProvider')]
    public function mapIsCreatedFromEntries(mixed $aKey, mixed $bKey): void
    {
        $aValue = new \stdClass();
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
    #[DataProvider('mapKeysDataProvider')]
    public function mapCanBeAssignedToOtherMap(mixed $aKey, mixed $bKey): void
    {
        $aValue = new \stdClass();
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
    #[DataProvider('mapKeysDataProvider')]
    public function mapKeysAndValuesAreFetched(mixed $aKey, mixed $bKey): void
    {
        $aValue = new \stdClass();
        $bValue = new \stdClass();

        $map = Map::fromEntries(
            [$aKey, $aValue],
            [$bKey, $bValue],
        );

        self::assertSame([$aKey, $bKey], $map->keys());
        self::assertSame([$aValue, $bValue], $map->values());
        self::assertSame([[$aKey, $aValue], [$bKey, $bValue]], $map->entries());
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
        $aKey = new \stdClass();
        $aKey->value = 'aValue';
        $bKey = new \stdClass();
        $bKey->value = 'bValue';
        $cKey = new \stdClass();
        $cKey->value = 'cValue';
        yield '=abc @a -a ?b' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 0, 'modifyKeys' => [$aKey], 'expectedKey' => $bKey, 'expectedValid' => true];
        yield '=abc @b -a ?b' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 1, 'modifyKeys' => [$aKey], 'expectedKey' => $bKey, 'expectedValid' => true];
        yield '=abc @c -a ?c' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 2, 'modifyKeys' => [$aKey], 'expectedKey' => $cKey, 'expectedValid' => true];
        yield '=abc @c! -a ?c' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 2, 'modifyKeys' => [$aKey], 'expectedKey' => $cKey, 'expectedValid' => true];
        yield '=abc @a -b ?a' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 0, 'modifyKeys' => [$bKey], 'expectedKey' => $aKey, 'expectedValid' => true];
        yield '=abc @b -b ?c' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 1, 'modifyKeys' => [$bKey], 'expectedKey' => $cKey, 'expectedValid' => true];
        yield '=abc @c -b ?c' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 2, 'modifyKeys' => [$bKey], 'expectedKey' => $cKey, 'expectedValid' => true];
        yield '=abc @a -c ?a' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 0, 'modifyKeys' => [$cKey], 'expectedKey' => $aKey, 'expectedValid' => true];
        yield '=abc @b -c ?b' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 1, 'modifyKeys' => [$cKey], 'expectedKey' => $bKey, 'expectedValid' => true];
        yield '=abc @c -c ?ø' => ['keys' => [$aKey, $bKey, $cKey], 'index' => 2, 'modifyKeys' => [$cKey], 'expectedKey' => null, 'expectedValid' => false];
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
