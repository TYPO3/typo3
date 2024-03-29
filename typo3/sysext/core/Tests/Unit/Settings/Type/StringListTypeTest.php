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
use TYPO3\CMS\Core\Settings\Type\StringListType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StringListTypeTest extends UnitTestCase
{
    public static function allowedValuesDataProvider(): array
    {
        $stringable = new class () implements \Stringable {
            public function __toString(): string
            {
                return 'string-from-stringable';
            }
        };
        return [
            'stringlist' => [
                ['foobar', $stringable, 'null'],
                ['foobar', 'string-from-stringable', 'null'],
            ],
            'numbers' => [
                ['1337', -10, 32425, PHP_INT_MAX, -10, 13.37, 0, '0'],
                ['1337', '-10', '32425', (string)PHP_INT_MAX, '-10', '13.37', '0', '0'],
            ],
            'bool' => [
                [true, false, 'true', 'false'],
                ['true', 'false', 'true', 'false'],
            ],
        ];
    }

    #[DataProvider('allowedValuesDataProvider')]
    #[Test]
    public function allowedValuesAreVerified(mixed $value): void
    {
        $stringListType = new StringListType(new NullLogger());

        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'stringlist',
            default: [],
            label: 'Unit Test setting',
        );
        self::assertTrue($stringListType->validate($value, $setting));
    }

    #[DataProvider('allowedValuesDataProvider')]
    #[Test]
    public function allowedValuesAreTransformed(mixed $value, array $expected): void
    {
        $stringListType = new StringListType(new NullLogger());

        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'stringlist',
            default: [],
            label: 'Unit Test setting',
        );
        self::assertEquals($expected, $stringListType->transformValue($value, $setting));
    }

    public static function disallowedValuesDataProvider(): array
    {
        return [
            'non list array' => [
                [1 => 'foo'],
            ],
            'sting key array' => [
                ['foo' => 'bar'],
            ],
            'string' => [
                'foobar',
            ],
            'int' => [
                1337,
            ],
            'float' => [
                13.37,
            ],
            'objects' => [
                new \stdClass(),
            ],
        ];
    }

    #[DataProvider('disallowedValuesDataProvider')]
    #[Test]
    public function disallowedValuesAreVerified(mixed $value): void
    {
        $stringListType = new StringListType(new NullLogger());
        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'stringlist',
            default: [],
            label: 'Unit Test setting',
        );
        self::assertFalse($stringListType->validate($value, $setting));
    }
}
