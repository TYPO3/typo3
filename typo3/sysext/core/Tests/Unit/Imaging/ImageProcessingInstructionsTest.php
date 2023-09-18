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

use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageProcessingInstructions;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ImageProcessingInstructionsTest extends UnitTestCase
{
    /**
     * @return iterable<
     *           string,
     *           array{array{int<0, max>, int<0, max>}, string, string, array, bool, ImageProcessingInstructions}
     *         >
     */
    public static function fromCropScaleValuesImageDataProvider(): iterable
    {
        $result = new ImageProcessingInstructions();
        $result->width = 150;
        $result->height = 120;
        $result->originalWidth = 150;
        $result->originalHeight = 0;
        yield 'Get image scale for a width of 150px' => [
            [170, 136],
            '150',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->width = 100;
        $result->height = 80;
        $result->originalWidth = 100;
        $result->originalHeight = 0;
        yield 'Get image scale with a maximum width of 100px' => [
            [170, 136],
            '',
            '',
            ['maxW' => 100],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->width = 200;
        $result->height = 136;
        $result->originalWidth = 0;
        $result->originalHeight = 0;
        yield 'Get image scale with a minimum width of 200px' => [
            [170, 136],
            '',
            '',
            ['minW' => 200],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 50;
        $result->width = 0;
        $result->height = 0;
        yield 'No PHP warning for zero in input dimensions when scaling' => [
            [0, 0],
            '50',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 50;
        $result->width = 50;
        $result->height = 450;
        yield 'width from original image and explicitly given scales an image down' => [
            [100, 900],
            '50',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 50;
        $result->originalHeight = 300;
        $result->width = 33;
        $result->height = 300;
        yield 'width from original image with maxH set, also fills "origH" value' => [
            [100, 900],
            '50',
            '',
            ['maxH' => 300],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 150;
        $result->originalHeight = 0;
        $result->width = 150;
        $result->height = 1350;
        yield 'width from original image and explicitly given scales an image up' => [
            [100, 900],
            '150',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 150;
        $result->originalHeight = 0;
        $result->width = 100;
        $result->height = 900;
        yield 'width from original image and explicitly given scales an image up but is disabled' => [
            [100, 900],
            '150',
            '',
            [],
            false,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 0;
        $result->originalHeight = 0;
        $result->width = 150;
        $result->height = 900;
        yield 'min width explicitly given scales an image up but is disabled resulting in do not keep aspect ratio' => [
            [100, 900],
            '',
            '',
            ['minW' => 150],
            false,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 50;
        $result->originalHeight = 100;
        $result->width = 0;
        $result->height = 0;
        yield 'no orig image given monitors "origW"' => [
            [0, 0],
            '50',
            '100',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 50;
        $result->originalHeight = 800;
        $result->width = 50;
        $result->height = 450;
        yield 'Incoming instructions use "m" in width with given height' => [
            [100, 900],
            '50m',
            '800',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 50;
        $result->originalHeight = 0;
        $result->width = 50;
        $result->height = 450;
        yield 'Incoming instructions use "m" in width without height' => [
            [100, 900],
            '50m',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 50;
        $result->originalHeight = 800;
        $result->width = 89;
        $result->height = 800;
        $result->useCropScaling = true;
        $result->cropArea = new Area(50, 800, 19.5, 0);
        yield 'Incoming instructions use "c" in width with given height' => [
            [100, 900],
            '50c',
            '800',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 50;
        $result->originalHeight = 0;
        $result->width = 50;
        $result->height = 450;
        $result->useCropScaling = true;
        $result->cropArea = new Area(50, 450, 0, 225);
        yield 'Incoming instructions use "c" in width but without height' => [
            [100, 900],
            '50c',
            '',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 50;
        $result->originalHeight = 650;
        $result->width = 72;
        $result->height = 650;
        $result->useCropScaling = true;
        $result->cropArea = new Area(50, 650, 13.2, 0);
        yield 'Incoming instructions use "c" in width on both sides' => [
            [100, 900],
            '50c20',
            '650c40',
            [],
            true,
            $result,
        ];

        $result = new ImageProcessingInstructions();
        $result->originalWidth = 50;
        $result->originalHeight = 800;
        $result->width = 89;
        $result->height = 800;
        $result->useCropScaling = true;
        $result->cropArea = new Area(50, 800, 23.4, 0);
        yield 'Incoming instructions use "c" in width on both sides with given height' => [
            [100, 900],
            '50c20',
            '800',
            [],
            true,
            $result,
        ];
    }

    /**
     * @test
     * @dataProvider fromCropScaleValuesImageDataProvider
     */
    public function fromCropScaleValuesTest(
        array $info,
        string $width,
        string $height,
        array $options,
        bool $allowUpscaling,
        ImageProcessingInstructions $expected
    ): void {
        $expected->fileExtension = 'jpg';
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowUpscaling'] = $allowUpscaling;
        $result = ImageProcessingInstructions::fromCropScaleValues($info, 'jpg', $width, $height, $options);
        self::assertEquals($expected, $result);
    }
}
