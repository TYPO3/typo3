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

namespace TYPO3\CMS\Core\Tests\Unit\Settings;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\Settings\SettingsDiff;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SettingsDiffTest extends UnitTestCase
{
    public static function diffResolvesSettingsDataProvider(): \Generator
    {
        $map = self::diffResolvesSettingsDataProviderForSettings([
            'other.space.key3' => false,
        ]);
        $tree = self::diffResolvesSettingsDataProviderForSettings([
            'other' => [
                'space' => [
                    'key3' => false,
                ],
            ],
        ]);

        foreach ($map as $test => $data) {
            yield '[map] ' . $test => $data;
        }
        foreach ($tree as $test => $data) {
            yield '[tree] ' . $test => $data;
        }
    }

    public static function diffResolvesSettingsDataProviderForSettings(array $localSettings): \Generator
    {
        $defaults = [
            'defaultSettings' => [
                'name.space.key1' => 'key1defaultValue',
                'name.space.key2' => 'key2defaultValueOverwriteBySystem',
                'other.space.key3' => true,
            ],
            'localSettings' => $localSettings,
        ];

        yield 'Set value to existing values' => [
            ...$defaults,
            'incomingSettings' => [
                'name.space.key1' => 'key1defaultValue',
                'name.space.key2' => 'key2defaultValueOverwriteBySystem',
                'other.space.key3' => false,
            ],
            'result' => [
                'other.space.key3' => false,
            ],
        ];

        yield 'Remove values set to system default, omit equal values' => [
            ...$defaults,
            'incomingSettings' => [
                'name.space.key1' => 'foobar',
                'name.space.key2' => 'key2defaultValueOverwriteBySystem',
                'other.space.key3' => true,
            ],
            'result' => [
                'name.space.key1' => 'foobar',
            ],
        ];

        yield 'Set value that equals definition default, but is overwritten in system defaults' => [
            ...$defaults,
            'incomingSettings' => [
                'name.space.key2' => 'key2defaultValue',
                'other.space.key3' => true,
            ],
            'result' => [
                'name.space.key2' => 'key2defaultValue',
            ],
        ];

        yield 'Set bool value to false' => [
            ...$defaults,
            'incomingSettings' => [
                'other.space.key3' => false,
            ],
            'result' => [
                'other.space.key3' => false,
            ],
        ];

        yield 'Set bool value to true' => [
            ...$defaults,
            'incomingSettings' => [
                'other.space.key3' => true,
            ],
            // value removed from result, as name.space.key3 defaults to true
            'result' => [],
        ];
    }

    #[DataProvider('diffResolvesSettingsDataProvider')]
    #[Test]
    public function diffResolvesSettings(
        array $defaultSettings,
        array $localSettings,
        array $incomingSettings,
        array $result
    ): void {
        $settings = SettingsDiff::create(
            $localSettings,
            new Settings($incomingSettings),
            new Settings($defaultSettings),
        );
        self::assertEquals($settings->asArray(), $result);
    }
}
