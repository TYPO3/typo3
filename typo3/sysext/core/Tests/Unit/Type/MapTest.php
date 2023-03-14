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

use TYPO3\CMS\Core\Type\Map;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class MapTest extends UnitTestCase
{
    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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
}
