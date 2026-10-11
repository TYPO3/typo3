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

namespace TYPO3\CMS\Core\Tests\Unit\Site;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingValue;
use TYPO3\CMS\Core\Site\SiteSettingsProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SiteSettingsProviderTest extends UnitTestCase
{
    public static function providedSettingsDataProvider(): \Generator
    {
        yield 'no settings yields only the defaults' => [
            'settings' => [],
            'expected' => [
                ['foo.bar', 'bar-default', 'foo.bar'],
                ['foo.baz', 'baz-default', 'foo.baz'],
                ['plain', 'plain-default', 'plain'],
            ],
        ];
        yield 'map value of a defined key' => [
            'settings' => ['foo.bar' => 'from-map'],
            'expected' => [
                ['foo.bar', 'bar-default', 'foo.bar'],
                ['foo.baz', 'baz-default', 'foo.baz'],
                ['plain', 'plain-default', 'plain'],
                ['foo.bar', 'from-map', 'foo.bar'],
            ],
        ];
        yield 'tree value of a defined key' => [
            'settings' => ['foo' => ['bar' => 'from-tree']],
            'expected' => [
                ['foo.bar', 'bar-default', 'foo.bar'],
                ['foo.baz', 'baz-default', 'foo.baz'],
                ['plain', 'plain-default', 'plain'],
                ['foo.bar', 'from-tree', 'foo.bar'],
            ],
        ];
        yield 'tree value of a defined key without dot' => [
            'settings' => ['plain' => 'from-tree'],
            'expected' => [
                ['foo.bar', 'bar-default', 'foo.bar'],
                ['foo.baz', 'baz-default', 'foo.baz'],
                ['plain', 'plain-default', 'plain'],
                ['plain', 'from-tree', 'plain'],
            ],
        ];
        yield 'tree with defined and anonymous siblings' => [
            'settings' => ['foo' => ['bar' => 'from-tree', 'other' => 'anonymous']],
            'expected' => [
                ['foo.bar', 'bar-default', 'foo.bar'],
                ['foo.baz', 'baz-default', 'foo.baz'],
                ['plain', 'plain-default', 'plain'],
                ['foo.bar', 'from-tree', 'foo.bar'],
                ['foo.other', 'anonymous', null],
            ],
        ];
        yield 'tree node below a defined key is kept as array value' => [
            'settings' => ['foo' => ['bar' => ['deep' => 'value']]],
            'expected' => [
                ['foo.bar', 'bar-default', 'foo.bar'],
                ['foo.baz', 'baz-default', 'foo.baz'],
                ['plain', 'plain-default', 'plain'],
                ['foo.bar', ['deep' => 'value'], 'foo.bar'],
            ],
        ];
        yield 'scalar tree node sharing the first segment of a defined key' => [
            'settings' => ['foo' => 'scalar'],
            'expected' => [
                ['foo.bar', 'bar-default', 'foo.bar'],
                ['foo.baz', 'baz-default', 'foo.baz'],
                ['plain', 'plain-default', 'plain'],
                ['foo', 'scalar', null],
            ],
        ];
        yield 'anonymous map key keeps its dot escaped' => [
            'settings' => ['undefined.key' => 'anonymous'],
            'expected' => [
                ['foo.bar', 'bar-default', 'foo.bar'],
                ['foo.baz', 'baz-default', 'foo.baz'],
                ['plain', 'plain-default', 'plain'],
                ['undefined\.key', 'anonymous', null],
            ],
        ];
        yield 'anonymous tree key' => [
            'settings' => ['undefined' => ['key' => 'anonymous']],
            'expected' => [
                ['foo.bar', 'bar-default', 'foo.bar'],
                ['foo.baz', 'baz-default', 'foo.baz'],
                ['plain', 'plain-default', 'plain'],
                ['undefined.key', 'anonymous', null],
            ],
        ];
        yield 'map value wins over tree value of the same key' => [
            'settings' => ['foo.bar' => 'from-map', 'foo' => ['bar' => 'from-tree']],
            'expected' => [
                ['foo.bar', 'bar-default', 'foo.bar'],
                ['foo.baz', 'baz-default', 'foo.baz'],
                ['plain', 'plain-default', 'plain'],
                ['foo.bar', 'from-tree', 'foo.bar'],
                ['foo.bar', 'from-map', 'foo.bar'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('providedSettingsDataProvider')]
    public function getProvidedSettingsResolvesMapAndTreeNotation(array $settings, array $expected): void
    {
        $definitions = [
            'foo.bar' => new SettingDefinition('foo.bar', 'string', 'bar-default', 'Bar'),
            'foo.baz' => new SettingDefinition('foo.baz', 'string', 'baz-default', 'Baz'),
            'plain' => new SettingDefinition('plain', 'string', 'plain-default', 'Plain'),
        ];
        $subject = new SiteSettingsProvider($settings, $definitions);

        $providedSettings = array_map(
            static fn(SettingValue $settingValue): array => [$settingValue->key, $settingValue->value, $settingValue->definition?->key],
            $subject->getProvidedSettings($definitions),
        );

        self::assertSame($expected, $providedSettings);
    }

    #[Test]
    public function getProvidedSettingsWithoutSettingsIgnoresDefinitionsOfEarlierProviders(): void
    {
        $definitions = [
            'foo.bar' => new SettingDefinition('foo.bar', 'string', 'bar-default', 'Bar'),
        ];
        $currentDefinitions = [
            'earlier.one' => new SettingDefinition('earlier.one', 'string', 'one-default', 'One'),
            'earlier.two' => new SettingDefinition('earlier.two', 'string', 'two-default', 'Two'),
            ...$definitions,
        ];
        $subject = new SiteSettingsProvider([], $definitions);

        $providedSettings = array_map(
            static fn(SettingValue $settingValue): array => [$settingValue->key, $settingValue->value, $settingValue->definition?->key],
            $subject->getProvidedSettings($currentDefinitions),
        );

        self::assertSame([['foo.bar', 'bar-default', 'foo.bar']], $providedSettings);
    }
}
