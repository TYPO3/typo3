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
use TYPO3\CMS\Core\Imaging\Effect\Edge;
use TYPO3\CMS\Core\Imaging\Effect\EffectParser;
use TYPO3\CMS\Core\Imaging\Effect\Emboss;
use TYPO3\CMS\Core\Imaging\Effect\Flip;
use TYPO3\CMS\Core\Imaging\Effect\Flop;
use TYPO3\CMS\Core\Imaging\Effect\Gamma;
use TYPO3\CMS\Core\Imaging\Effect\Gray;
use TYPO3\CMS\Core\Imaging\Effect\Invert;
use TYPO3\CMS\Core\Imaging\Effect\Rotate;
use TYPO3\CMS\Core\Imaging\Effect\Sharpen;
use TYPO3\CMS\Core\Imaging\Effect\Shear;
use TYPO3\CMS\Core\Imaging\Effect\Solarize;
use TYPO3\CMS\Core\Imaging\Effect\Swirl;
use TYPO3\CMS\Core\Imaging\Effect\Wave;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class EffectParserTest extends UnitTestCase
{
    public static function parseCreatesEffectFromKeywordDataProvider(): array
    {
        return [
            'blur' => ['blur = 20', new Blur(20)],
            'charcoal' => ['charcoal = 3', new Charcoal(3)],
            'colors' => ['colors = 8', new Colors(8)],
            'edge' => ['edge', new Edge()],
            'emboss' => ['emboss', new Emboss()],
            'flip' => ['flip', new Flip()],
            'flop' => ['flop', new Flop()],
            'gamma' => ['gamma = 1.5', new Gamma(1.5)],
            'gray' => ['gray', new Gray()],
            'invert' => ['invert', new Invert()],
            'rotate' => ['rotate = 45.5', new Rotate(45.5)],
            'shear' => ['shear = 20', new Shear(20)],
            'sharpen' => ['sharpen = 30', new Sharpen(30)],
            'solarize' => ['solarize = 40', new Solarize(40)],
            'swirl' => ['swirl = 90', new Swirl(90)],
            'wave' => ['wave = 5,20', new Wave(5, 20)],
            'wave without wavelength' => ['wave = 5', new Wave(5)],
            'keyword in upper case' => ['ROTATE = 90', new Rotate(90)],
            'value is clamped' => ['blur = 500', new Blur(99)],
            'value is ignored for effects without one' => ['gray = 50', new Gray()],
        ];
    }

    #[Test]
    #[DataProvider('parseCreatesEffectFromKeywordDataProvider')]
    public function parseCreatesEffectFromKeyword(string $setup, object $expected): void
    {
        self::assertEquals([$expected], new EffectParser()->parse($setup));
    }

    #[Test]
    public function parseKeepsTheOrderOfEffects(): void
    {
        self::assertEquals(
            [new Gray(), new Rotate(90), new Wave(10, 30)],
            new EffectParser()->parse('gray | rotate = 90 |wave=10,30')
        );
    }

    #[Test]
    public function parseIgnoresUnknownKeywordsAndEmptyParts(): void
    {
        self::assertEquals(
            [new Flip()],
            new EffectParser()->parse('| unknown = 3 || flip | ')
        );
    }
}
