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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging\Effect;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\Effect\Blur;
use TYPO3\CMS\Core\Imaging\Effect\Charcoal;
use TYPO3\CMS\Core\Imaging\Effect\Colors;
use TYPO3\CMS\Core\Imaging\Effect\Gamma;
use TYPO3\CMS\Core\Imaging\Effect\Rotate;
use TYPO3\CMS\Core\Imaging\Effect\Sharpen;
use TYPO3\CMS\Core\Imaging\Effect\Shear;
use TYPO3\CMS\Core\Imaging\Effect\Solarize;
use TYPO3\CMS\Core\Imaging\Effect\Swirl;
use TYPO3\CMS\Core\Imaging\Effect\Wave;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class EffectTest extends UnitTestCase
{
    public static function boundaryAndClampDataProvider(): array
    {
        return [
            'blur min boundary' => [static fn() => new Blur(1), 'strength', 1],
            'blur max boundary' => [static fn() => new Blur(99), 'strength', 99],
            'blur clamps below range' => [static fn() => new Blur(0), 'strength', 1],
            'blur clamps above range' => [static fn() => new Blur(150), 'strength', 99],
            'charcoal min boundary' => [static fn() => new Charcoal(1), 'radius', 1],
            'charcoal max boundary' => [static fn() => new Charcoal(5), 'radius', 5],
            'charcoal clamps below range' => [static fn() => new Charcoal(0), 'radius', 1],
            'charcoal clamps above range' => [static fn() => new Charcoal(99), 'radius', 5],
            'colors min boundary' => [static fn() => new Colors(2), 'count', 2],
            'colors max boundary' => [static fn() => new Colors(255), 'count', 255],
            'colors clamps below range' => [static fn() => new Colors(1), 'count', 2],
            'colors clamps above range' => [static fn() => new Colors(512), 'count', 255],
            'gamma min boundary' => [static fn() => new Gamma(0.1), 'value', 0.1],
            'gamma max boundary' => [static fn() => new Gamma(10.0), 'value', 10.0],
            'gamma clamps below range' => [static fn() => new Gamma(0.0), 'value', 0.1],
            'gamma clamps above range' => [static fn() => new Gamma(100.0), 'value', 10.0],
            'rotate keeps an angle within one turn' => [static fn() => new Rotate(90), 'degrees', 90.0],
            'rotate keeps a fractional angle' => [static fn() => new Rotate(45.5), 'degrees', 45.5],
            'rotate wraps a full turn to 0' => [static fn() => new Rotate(360), 'degrees', 0.0],
            'rotate wraps a negative angle' => [static fn() => new Rotate(-30), 'degrees', 330.0],
            'rotate wraps an angle above one turn' => [static fn() => new Rotate(450), 'degrees', 90.0],
            'rotate wraps several turns' => [static fn() => new Rotate(-810.5), 'degrees', 269.5],
            'rotate treats a non-finite angle as 0' => [static fn() => new Rotate(INF), 'degrees', 0.0],
            'shear min boundary' => [static fn() => new Shear(-85), 'degrees', -85],
            'shear max boundary' => [static fn() => new Shear(85), 'degrees', 85],
            'shear clamps below range' => [static fn() => new Shear(-90), 'degrees', -85],
            'shear clamps above range' => [static fn() => new Shear(90), 'degrees', 85],
            'sharpen min boundary' => [static fn() => new Sharpen(1), 'strength', 1],
            'sharpen max boundary' => [static fn() => new Sharpen(99), 'strength', 99],
            'sharpen clamps below range' => [static fn() => new Sharpen(0), 'strength', 1],
            'sharpen clamps above range' => [static fn() => new Sharpen(200), 'strength', 99],
            'solarize min boundary' => [static fn() => new Solarize(0), 'threshold', 0],
            'solarize max boundary' => [static fn() => new Solarize(99), 'threshold', 99],
            'solarize clamps below range' => [static fn() => new Solarize(-1), 'threshold', 0],
            'solarize clamps above range' => [static fn() => new Solarize(255), 'threshold', 99],
            'swirl min boundary' => [static fn() => new Swirl(-720), 'degrees', -720],
            'swirl max boundary' => [static fn() => new Swirl(720), 'degrees', 720],
            'swirl clamps below range' => [static fn() => new Swirl(-9999), 'degrees', -720],
            'swirl clamps above range' => [static fn() => new Swirl(9999), 'degrees', 720],
        ];
    }

    #[Test]
    #[DataProvider('boundaryAndClampDataProvider')]
    public function constructorClampsInputToValidRange(\Closure $construct, string $property, int|float $expected): void
    {
        self::assertSame($expected, $construct()->{$property});
    }

    public static function waveClampDataProvider(): array
    {
        return [
            'both min' => [0, 1, 0, 1],
            'both max' => [99, 99, 99, 99],
            'amplitude below range' => [-5, 30, 0, 30],
            'amplitude above range' => [200, 30, 99, 30],
            'wavelength below range' => [10, 0, 10, 1],
            'wavelength above range' => [10, 500, 10, 99],
        ];
    }

    #[Test]
    #[DataProvider('waveClampDataProvider')]
    public function waveClampsBothParameters(int $amplitude, int $wavelength, int $expectedAmplitude, int $expectedWavelength): void
    {
        $wave = new Wave($amplitude, $wavelength);
        self::assertSame($expectedAmplitude, $wave->amplitude);
        self::assertSame($expectedWavelength, $wave->wavelength);
    }

    #[Test]
    public function constructorsExposeReasonableDefaults(): void
    {
        self::assertSame(10, new Blur()->strength);
        self::assertSame(1, new Charcoal()->radius);
        self::assertSame(16, new Colors()->count);
        self::assertSame(1.0, new Gamma()->value);
        self::assertSame(0.0, new Rotate()->degrees);
        self::assertSame(0, new Shear()->degrees);
        self::assertSame(10, new Sharpen()->strength);
        self::assertSame(50, new Solarize()->threshold);
        self::assertSame(180, new Swirl()->degrees);
        $wave = new Wave();
        self::assertSame(10, $wave->amplitude);
        self::assertSame(30, $wave->wavelength);
    }
}
