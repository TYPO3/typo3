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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging\ImageManipulation;

use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Ratio;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AreaTest extends UnitTestCase
{
    /**
     * @test
     */
    public function makeRelativeToFileReducesSizes(): void
    {
        $imageArea = new Area(50.0, 50.0, 100.0, 100.0);
        $imageFixture = new File(
            [],
            $this->getMockBuilder(ResourceStorage::class)->disableOriginalConstructor()->getMock(),
            ['width' => 100, 'height' => 200]
        );
        $relativeArea = $imageArea->makeRelativeBasedOnFile($imageFixture);
        $expectedResult = [
            'x' => 0.5,
            'y' => 0.25,
            'width' => 1.0,
            'height' => 0.5,
        ];
        self::assertSame($expectedResult, $relativeArea->asArray());
    }

    public function applyRatioRestrictsAreaToRespectRatioDataProvider(): array
    {
        return [
            [
                [0.0, 0.0, 1, 1],
                4 / 3
            ],
            [
                [0.0, 0.0, 1, 1],
                3 / 4
            ],
            [
                [0.1, 0.1, 0.2, 0.4],
                4 / 3,
            ],
            [
                [0.1, 0.1, 0.4, 0.2],
                1.0
            ],
        ];
    }

    /**
     * @param array $areaSize
     * @param $ratio
     * @test
     * @dataProvider applyRatioRestrictsAreaToRespectRatioDataProvider
     */
    public function applyRatioRestrictsAreaToRespectRatio(array $areaSize, $ratio): void
    {
        $area = new Area(...$areaSize);
        $ratioFixture = new Ratio('dummy', 'dummy', $ratio);
        $areaData = $area->applyRatioRestriction($ratioFixture)->asArray();
        self::assertSame($areaData['width'] / $areaData['height'], $ratio);
    }

    /**
     * @test
     */
    public function applyRatioDoesNothingForFreeRatio(): void
    {
        $area = new Area(0.1, 0.1, 0.2, 0.4);
        $ratioFixture = new Ratio('dummy', 'dummy', 0.0);
        $croppedArea = $area->applyRatioRestriction($ratioFixture);
        self::assertSame($area, $croppedArea);
    }
}
