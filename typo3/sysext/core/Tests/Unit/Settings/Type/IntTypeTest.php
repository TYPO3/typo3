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

namespace TYPO3\CMS\Core\Tests\Unit\Settings\Type;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\Type\IntType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class IntTypeTest extends UnitTestCase
{
    public static function allowedValuesDataProvider(): array
    {
        return [
            'negativeValue' => [-10, -10],
            'int' => [32425, 32425],
            'negative int' => [-32425, -32425],
            'largest int' => [PHP_INT_MAX, PHP_INT_MAX],
            'int as string' => ['32425', 32425],
            'negative int as string' => ['-32425', -32425],
            'zero' => [0, 0],
            'zero as string' => ['0', 0],
        ];
    }

    #[DataProvider('allowedValuesDataProvider')]
    #[Test]
    public function allowedValuesAreVerified(mixed $value): void
    {
        $intType = new IntType(new NullLogger());

        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'int',
            default: 1337,
            label: 'Unit Test setting',
        );
        self::assertTrue($intType->validate($value, $setting));
    }

    #[DataProvider('allowedValuesDataProvider')]
    #[Test]
    public function allowedValuesAreTransformed(mixed $value, int $expected): void
    {
        $intType = new IntType(new NullLogger());

        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'int',
            default: 1337,
            label: 'Unit Test setting',
        );
        self::assertEquals($expected, $intType->transformValue($value, $setting));
    }

    public static function disallowedValuesDataProvider(): array
    {
        return [
            'true' => [true],
            'false' => [false],
            'float as string' => ['32.325'],
            'number with prefixes' => ['0032425'],
            'objects' => [new \stdClass()],
        ];
    }

    #[DataProvider('disallowedValuesDataProvider')]
    #[Test]
    public function disallowedValuesAreVerified(mixed $value): void
    {
        $intType = new IntType(new NullLogger());
        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'int',
            default: 1337,
            label: 'Unit Test setting',
        );
        self::assertFalse($intType->validate($value, $setting));
    }
}
