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
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GraphicalFunctionsTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * Dataprovider for getScaleForImage
     */
    public static function getScaleForImageDataProvider(): \Generator
    {
        yield 'Get image scale for a width of 150px' => [
            [170, 136],
            '150',
            '',
            [],
            true,
            [
                'crs' => false,
                'origW' => 150,
                'origH' => 0,
                'max' => 0,
                0 => 150,
                1 => 120,
            ],
        ];
        yield 'Get image scale with a maximum width of 100px' => [
            [170, 136],
            '',
            '',
            [
                'maxW' => 100,
            ],
            true,
            [
                'crs' => false,
                'origW' => 100,
                'origH' => 0,
                'max' => 1,
                0 => 100,
                1 => 80,
            ],
        ];
        yield 'Get image scale with a minimum width of 200px' => [
            [170, 136],
            '',
            '',
            [
                'minW' => 200,
            ],
            true,
            [
                'crs' => false,
                'origW' => 0,
                'origH' => 0,
                'max' => 0,
                0 => 200,
                1 => 136,
            ],
        ];
        yield 'No PHP warning for zero in input dimensions when scaling' => [
            [0, 0],
            '50',
            '',
            [],
            true,
            [
                'crs' => false,
                'origW' => 50,
                'origH' => 0,
                'max' => 0,
                0 => 0,
                1 => 0,
            ],
        ];
        yield 'width from original image and explicitly given scales an image down' => [
            [100, 900],
            '50',
            '',
            [],
            true,
            [
                'crs' => false,
                'origW' => 50,
                'origH' => 0,
                'max' => 0,
                0 => 50,
                1 => 450,
            ],
        ];
        yield 'width from original image with maxH set, also fills "origH" value' => [
            [100, 900],
            '50',
            '',
            ['maxH' => 300],
            true,
            [
                'crs' => false,
                'origW' => 50,
                'origH' => 300,
                'max' => 1,
                0 => 33,
                1 => 300,
            ],
        ];
        yield 'width from original image and explicitly given scales an image up' => [
            [100, 900],
            '150',
            '',
            [],
            true,
            [
                'crs' => false,
                'origW' => 150,
                'origH' => 0,
                'max' => 0,
                0 => 150,
                1 => 1350,
            ],
        ];
        yield 'width from original image and explicitly given scales an image up but is disabled' => [
            [100, 900],
            '150',
            '',
            [],
            false,
            [
                'crs' => false,
                'origW' => 150,
                'origH' => 0,
                'max' => 0,
                0 => 100,
                1 => 900,
            ],
            false,
        ];
        yield 'min width explicitly given scales an image up but is disabled resulting in do not keep aspect ratio' => [
            [100, 900],
            '',
            '',
            ['minW' => 150],
            false,
            [
                'crs' => false,
                'origW' => 0,
                'origH' => 0,
                'max' => 0,
                0 => 150,
                1 => 900,
            ],
            false,
        ];
        yield 'no orig image given monitors "origW"' => [
            [0, 0],
            '50',
            '100',
            [],
            true,
            [
                'crs' => false,
                'origW' => 50,
                'origH' => 100,
                'max' => 0,
                0 => 0,
                1 => 0,
            ],
        ];
        yield 'Incoming instructions use "m" in width with given height' => [
            [100, 900],
            '50m',
            '800',
            [],
            true,
            [
                'crs' => false,
                'origW' => 50,
                'origH' => 800,
                'max' => 1,
                0 => 50,
                1 => 450,
            ],
        ];
        yield 'Incoming instructions use "m" in width without height' => [
            [100, 900],
            '50m',
            '',
            [],
            true,
            [
                'crs' => false,
                'origW' => 50,
                'origH' => 0,
                'max' => 1,
                0 => 50,
                1 => 450,
            ],
        ];
        yield 'Incoming instructions use "c" in width with given height' => [
            [100, 900],
            '50c',
            '800',
            [],
            true,
            [
                'cropH' => 0,
                'cropV' => 0,
                'crs' => true,
                'origW' => 50,
                'origH' => 800,
                'max' => 0,
                0 => 89,
                1 => 800,
            ],
        ];
        yield 'Incoming instructions use "c" in width but without height' => [
            [100, 900],
            '50c',
            '',
            [],
            true,
            [
                'cropH' => 0,
                'cropV' => 0,
                'crs' => true,
                'origW' => 50,
                'origH' => 0,
                'max' => 0,
                0 => 50,
                1 => 450,
            ],
        ];
        yield 'Incoming instructions use "c" in width on both sides' => [
            [100, 900],
            '50c20',
            '650c40',
            [],
            true,
            [
                'cropH' => 20,
                'cropV' => 40,
                'crs' => true,
                'origW' => 50,
                'origH' => 650,
                'max' => 0,
                0 => 72,
                1 => 650,
            ],
        ];
        yield 'Incoming instructions use "c" in width on both sides with given height' => [
            [100, 900],
            '50c20',
            '800',
            [],
            true,
            [
                'cropH' => 20,
                'cropV' => 0,
                'crs' => true,
                'origW' => 50,
                'origH' => 800,
                'max' => 0,
                0 => 89,
                1 => 800,
            ],
        ];
    }

    #[DataProvider('getScaleForImageDataProvider')]
    #[Test]
    public function getScaleForImage(array $info, string $width, string $height, array $options, bool $mayScaleUp, array $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowUpscaling'] = $mayScaleUp;
        $subject = new GraphicalFunctions();
        $result = $subject->getImageScale($info, $width, $height, $options);
        self::assertSame($result, $expected);
    }

    #[Test]
    public function imageMagickIdentifyReturnsFormattedValues(): void
    {
        $file = 'myImageFile.png';
        $expected = [
            '123',
            '234',
            'png',
            'myImageFile.png',
            'png',
        ];

        $subject = $this->getAccessibleMock(GraphicalFunctions::class, ['executeIdentifyCommandForImageFile'], [], '', false);
        $subject->_set('processorEnabled', true);
        $subject->expects(self::once())->method('executeIdentifyCommandForImageFile')->with($file)->willReturn('123 234 png PNG');
        $result = $subject->imageMagickIdentify($file);
        self::assertEquals($result, $expected);
    }

    #[Test]
    public function imageMagickIdentifyReturnsFormattedValuesWithOffset(): void
    {
        $file = 'myImageFile.png';
        $expected = [
            '200+0+0',
            '400+0+0',
            'png',
            'myImageFile.png',
            'png',
        ];

        $subject = $this->getAccessibleMock(GraphicalFunctions::class, ['executeIdentifyCommandForImageFile'], [], '', false);
        $subject->_set('processorEnabled', true);
        $subject->expects(self::once())->method('executeIdentifyCommandForImageFile')->with($file)->willReturn('200+0+0 400+0+0 png PNG');
        $result = $subject->imageMagickIdentify($file);
        self::assertEquals($result, $expected);
    }
}
