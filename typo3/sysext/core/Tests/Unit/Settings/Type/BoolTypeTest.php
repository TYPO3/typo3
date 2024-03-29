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
use TYPO3\CMS\Core\Settings\Type\BoolType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BoolTypeTest extends UnitTestCase
{
    public static function allowedValuesDataProvider(): array
    {
        return [
            'true' => [true, true],
            'false' => [false, false],
            'true as string' => ['true', true],
            'false as string' => ['false', false],
            '1 as string' => ['1', true],
            '0 as string' => ['0', false],
            'on as string' => ['on', true],
            'off as string' => ['off', false],
            'yes as string' => ['yes', true],
            'no as string' => ['no', false],
        ];
    }

    #[DataProvider('allowedValuesDataProvider')]
    #[Test]
    public function allowedValuesAreVerified(mixed $value): void
    {
        $boolType = new BoolType(new NullLogger());

        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'bool',
            default: true,
            label: 'Unit Test setting',
        );
        self::assertTrue($boolType->validate($value, $setting));
    }

    #[DataProvider('allowedValuesDataProvider')]
    #[Test]
    public function allowedValuesAreTransformed(mixed $value, bool $expected): void
    {
        $boolType = new BoolType(new NullLogger());

        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'bool',
            default: true,
            label: 'Unit Test setting',
        );
        self::assertEquals($expected, $boolType->transformValue($value, $setting));
    }

    public static function disallowedValuesDataProvider(): array
    {
        return [
            'random string' => ['1337'],
            'random int' => [1337],
            'object' => [new \stdClass()],
        ];
    }

    #[DataProvider('disallowedValuesDataProvider')]
    #[Test]
    public function disallowedValuesAreVerified(mixed $value): void
    {
        $boolType = new BoolType(new NullLogger());
        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'bool',
            default: true,
            label: 'Unit Test setting',
        );
        self::assertFalse($boolType->validate($value, $setting));
    }
}
