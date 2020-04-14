<?php

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

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class GraphicalFunctionsTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * Dataprovider for getScaleForImage
     *
     * @return array
     */
    public function getScaleForImageDataProvider()
    {
        return [
            'Get image scale for a width of 150px' => [
                [
                    170,
                    136,
                ],
                '150',
                '',
                [],
                [
                    'crs' => false,
                    'origW' => 150,
                    'origH' => 0,
                    'max' => 0,
                    0 => 150,
                    1 => (float)120
                ],
            ],
            'Get image scale with a maximum width of 100px' => [
                [
                    170,
                    136,
                ],
                '',
                '',
                [
                    'maxW' => 100
                ],
                [
                    'crs' => false,
                    'origW' => 100,
                    'origH' => 0,
                    'max' => 1,
                    0 => 100,
                    1 => (float)80
                ],
            ],
            'Get image scale with a minimum width of 200px' => [
                [
                    170,
                    136,
                ],
                '',
                '',
                [
                    'minW' => 200
                ],
                [
                    'crs' => false,
                    'origW' => 0,
                    'origH' => 0,
                    'max' => 0,
                    0 => 200,
                    1 => (float)136
                ],
            ],
            'No PHP warning for zero in input dimensions when scaling' => [
                [0, 0],
                '50',
                '',
                [],
                [
                    'crs' => false,
                    'origW' => 50,
                    'origH' => 0,
                    'max' => 0,
                    0 => 0,
                    1 => 0
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getScaleForImageDataProvider
     */
    public function getScaleForImage($info, $width, $height, $options, $expected)
    {
        $result = (new GraphicalFunctions())->getImageScale($info, $width, $height, $options);
        self::assertEquals($result, $expected);
    }

    /**
     * @test
     */
    public function imageMagickIdentifyReturnsFormattedValues()
    {
        $file = 'myImageFile.png';
        $expected = [
            '123',
            '234',
            'png',
            'myImageFile.png',
            'png'
        ];

        $subject = $this->getAccessibleMock(GraphicalFunctions::class, ['executeIdentifyCommandForImageFile'], [], '', false);
        $subject->_set('processorEnabled', true);
        $subject->expects(self::once())->method('executeIdentifyCommandForImageFile')->with($file)->willReturn('123 234 png PNG');
        $result = $subject->imageMagickIdentify($file);
        self::assertEquals($result, $expected);
    }

    /**
     * @test
     */
    public function imageMagickIdentifyReturnsFormattedValuesWithOffset()
    {
        $file = 'myImageFile.png';
        $expected = [
            '200+0+0',
            '400+0+0',
            'png',
            'myImageFile.png',
            'png'
        ];

        $subject = $this->getAccessibleMock(GraphicalFunctions::class, ['executeIdentifyCommandForImageFile'], [], '', false);
        $subject->_set('processorEnabled', true);
        $subject->expects(self::once())->method('executeIdentifyCommandForImageFile')->with($file)->willReturn('200+0+0 400+0+0 png PNG');
        $result = $subject->imageMagickIdentify($file);
        self::assertEquals($result, $expected);
    }
}
