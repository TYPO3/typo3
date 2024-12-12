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
use TYPO3\CMS\Core\Settings\Type\ColorType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ColorTypeTest extends UnitTestCase
{
    public static function allowedValuesDataProvider(): array
    {
        return [
            '6 char hex' => ['#0246ef', '#0246ef'],
            '8 char hex' => ['#0246efcc', '#0246efcc'],
            '3 char hex' => ['#fff', '#fff'],
            '6 char uppercase hex' => ['#0246EF', '#0246EF'],
            '8 char uppercase hex' => ['#0246EFCC', '#0246EFCC'],
            '3 char uppercase hex' => ['#FFF', '#FFF'],
            'rgb' => ['rgb(10, 20,30)', 'rgb(10,20,30)'],
            'rgb with space separator' => ['rgb(10 20 30)', 'rgb(10,20,30)'],
            'rgb with alpha' => ['rgb(10 20 30 / 0.3)', 'rgba(10,20,30,0.3)'],
            'rgba' => ['rgba(10, 20,30, 0.3)', 'rgba(10,20,30,0.3)'],
        ];
    }

    #[DataProvider('allowedValuesDataProvider')]
    #[Test]
    public function allowedValuesAreVerified(mixed $value): void
    {
        $colorType = new ColorType(new NullLogger());

        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'color',
            default: '#000',
            label: 'Unit Test setting',
        );
        self::assertTrue($colorType->validate($value, $setting));
    }

    #[DataProvider('allowedValuesDataProvider')]
    #[Test]
    public function allowedValuesAreTransformed(mixed $value, string $expected): void
    {
        $colorType = new ColorType(new NullLogger());

        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'color',
            default: '#000',
            label: 'Unit Test setting',
        );
        self::assertEquals($expected, $colorType->transformValue($value, $setting));
    }

    public static function disallowedValuesDataProvider(): array
    {
        return [
            '6 char hex with invalid chars' => ['#0246eg'],
            '8 char hex with invalid chars' => ['#0246efcz'],
            '5 char hex' => ['#ff444'],
            '4 char hex' => ['#ff43'],
            '3 char hex with invalid chars' => ['#fi4'],
            'rgb with invalid numbers' => ['rgb(10, 257,30)'],
            'rgba with invalid numbers' => ['rgba(10, 20,30, 1.3)'],
            'invalid string' => ['#<img'],
            //'objects' => [new \stdClass()],
        ];
    }

    #[DataProvider('disallowedValuesDataProvider')]
    #[Test]
    public function disallowedValuesAreVerified(mixed $value): void
    {
        $colorType = new ColorType(new NullLogger());
        $setting = new SettingDefinition(
            key: 'unit.test',
            type: 'color',
            default: '#000',
            label: 'Unit Test setting',
        );
        self::assertFalse($colorType->validate($value, $setting));
    }
}
