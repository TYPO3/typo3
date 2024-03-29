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
use TYPO3\CMS\Core\Settings\Type\StringType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StringTypeTest extends UnitTestCase
{
    public static function allowedValuesDataProvider(): array
    {
        return [
            'string' => ['foobar', 'foobar'],
            'negativeValue' => [-10, '-10'],
            'int' => [32425, '32425'],
            'negative int' => [-32425, '-32425'],
            'largest int' => [PHP_INT_MAX, (string)PHP_INT_MAX],
            'float' => [13.37, '13.37'],
            'int as string' => ['32425', '32425'],
            'negative int as string' => ['-32425', '-32425'],
            'float as string' => ['13.37', '13.37'],
            'zero' => [0, '0'],
            'zero as string' => ['0', '0'],
            'null as string' => ['null', 'null'],
            'stringable' => [
                new class () implements \Stringable {
                    public function __toString(): string
                    {
                        return 'string-from-stringable';
                    }
                },
                'string-from-stringable',
            ],
        ];
    }

    #[DataProvider('allowedValuesDataProvider')]
    #[Test]
    public function allowedValuesAreVerified(mixed $value): void
    {
        $stringType = new StringType(new NullLogger());

        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'string',
            default: 'foobar',
            label: 'Unit Test setting',
        );
        self::assertTrue($stringType->validate($value, $setting));
    }

    #[DataProvider('allowedValuesDataProvider')]
    #[Test]
    public function allowedValuesAreTransformed(mixed $value, string $expected): void
    {
        $stringType = new StringType(new NullLogger());

        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'string',
            default: 'foobar',
            label: 'Unit Test setting',
        );
        self::assertEquals($expected, $stringType->transformValue($value, $setting));
    }

    public static function disallowedValuesDataProvider(): array
    {
        return [
            'objects' => [new \stdClass()],
        ];
    }

    #[DataProvider('disallowedValuesDataProvider')]
    #[Test]
    public function disallowedValuesAreVerified(mixed $value): void
    {
        $stringType = new StringType(new NullLogger());
        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'string',
            default: 'foobar',
            label: 'Unit Test setting',
        );
        self::assertFalse($stringType->validate($value, $setting));
    }
}
