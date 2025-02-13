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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageProcessingInstructions;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ImageProcessingInstructionsTest extends UnitTestCase
{
    /**
     * @return iterable<
     *           string,
     *           array{int<0, max>, int<0, max>, string|int, string|int, array, bool, ImageProcessingInstructions}
     *         >
     */
    public static function fromCropScaleValuesImageDataProvider(): iterable
    {
        $result = new ImageProcessingInstructions(
            width: 150,
            height: 120,
        );
        yield 'Get image scale for a width of 150px' => [
            170,
            136,
            '150',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 100,
            height: 80,
        );
        yield 'Get image scale with a maximum width of 100px' => [
            170,
            136,
            '',
            '',
            ['maxW' => 100],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 64,
            height: 1,
        );
        yield 'Get image scale with a maximum width+height of 64px (standard preview image) with extreme WIDTH proportions' => [
            1920,
            10,
            '',
            '',
            ['maxWidth' => 64, 'maxHeight' => 64],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 1,
            height: 64,
        );
        yield 'Get image scale with a maximum width+height of 64px (standard preview image) with extreme HEIGHT proportions' => [
            10,
            1920,
            '',
            '',
            ['maxWidth' => 64, 'maxHeight' => 64],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 200,
            height: 160,
        );
        yield 'Get image scale with a minimum width of 200px' => [
            170,
            136,
            '',
            '',
            ['minW' => 200],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 0,
            height: 0,
        );
        yield 'No PHP warning for zero in input dimensions when scaling' => [
            0,
            0,
            '50',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 50,
            height: 450,
        );
        yield 'width from original image and explicitly given scales an image down' => [
            100,
            900,
            '50',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 33,
            height: 300,
        );
        yield 'width from original image with maxH set, also fills "origH" value' => [
            100,
            900,
            '50',
            '',
            ['maxH' => 300],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 150,
            height: 1350,
        );
        yield 'width from original image and explicitly given scales an image up' => [
            100,
            900,
            '150',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 100,
            height: 900,
        );
        yield 'width from original image and explicitly given scales an image up but is disabled' => [
            100,
            900,
            '150',
            '',
            [],
            false,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 100,
            height: 900,
        );
        yield 'min width explicitly given scales an image up but is disabled' => [
            100,
            900,
            '',
            '',
            ['minW' => 150],
            false,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 0,
            height: 0,
        );
        yield 'no orig image given monitors "origW"' => [
            0,
            0,
            '50',
            '100',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 50,
            height: 450,
        );
        yield 'Incoming instructions use "m" in width with given height' => [
            100,
            900,
            '50m',
            '800',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 50,
            height: 450,
        );
        yield 'Incoming instructions use "m" in width with given height as int' => [
            100,
            900,
            '50m',
            800,
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 50,
            height: 450,
        );
        yield 'Incoming instructions use "m" in width without height' => [
            100,
            900,
            '50m',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 50,
            height: 800,
            cropArea: new Area(21.875, 0, 56.25 /* 900 / 800 * 50 */, 900),
        );
        yield 'Incoming instructions use "c" in width with given height' => [
            100,
            900,
            '50c',
            '800',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 50,
            height: 450,
            cropArea: null,
        );
        yield 'Incoming instructions use "c" in width but without height' => [
            100,
            900,
            '50c',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 50,
            height: 650,
            cropArea: new Area(18.461538461538456, 0, 69.23076923076924 /* 900 / 650 * 50 */, 900),
        );
        yield 'Incoming instructions use "c" in width on both sides' => [
            100,
            900,
            '50c20',
            '650c40',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions(
            width: 50,
            height: 800,
            cropArea: new Area(26.25, 0, 56.25 /* 900 / 800 * 50 */, 900),
        );
        yield 'Incoming instructions use "c" in width on both sides with given height' => [
            100,
            900,
            '50c20',
            '800',
            [],
            true,
            $result,
        ];
    }

    /**
     * @param int<0, max> $incomingWidth
     * @param int<0, max> $incomingHeight
     */
    #[DataProvider('fromCropScaleValuesImageDataProvider')]
    #[Test]
    public function fromCropScaleValuesTest(
        int $incomingWidth,
        int $incomingHeight,
        int|string $width,
        int|string $height,
        array $options,
        bool $allowUpscaling,
        ImageProcessingInstructions $expected
    ): void {
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowUpscaling'] = $allowUpscaling;
        $result = ImageProcessingInstructions::fromCropScaleValues($incomingWidth, $incomingHeight, $width, $height, $options);
        self::assertEquals($expected, $result);
    }
}
