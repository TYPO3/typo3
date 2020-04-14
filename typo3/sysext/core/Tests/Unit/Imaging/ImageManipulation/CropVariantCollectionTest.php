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
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariant;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Imaging\ImageManipulation\InvalidConfigurationException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CropVariantCollectionTest extends UnitTestCase
{
    /**
     * @var array
     */
    private static $tca = [
        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.default',
        'cropArea' => [
            'x' => 0.0,
            'y' => 0.0,
            'width' => 1.0,
            'height' => 1.0,
        ],
        'allowedAspectRatios' => [
            '16:9' => [
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.16_9',
                'value' => 1.777777777777777
            ],
            '4:3' => [
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.4_3',
                'value' => 1.333333333333333
            ],
            '1:1' => [
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.1_1',
                'value' => 1.0
            ],
            'free' => [
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.free',
                'value' => 0.0
            ],
        ],
        'selectedRatio' => '16:9',
        'focusArea' => [
            'x' => 0.4,
            'y' => 0.4,
            'width' => 0.6,
            'height' => 0.6,
        ],
        'coverAreas' => [
            [
                'x' => 0.0,
                'y' => 0.8,
                'width' => 1.0,
                'height' => 0.2,
            ]
        ],
    ];

    /**
     * @test
     */
    public function createFromJsonWorks()
    {
        $cropVariant1 = self::$tca;
        $cropVariant2 = self::$tca;
        $cropVariantCollection = CropVariantCollection::create(json_encode(['default' => $cropVariant1, 'Second' => $cropVariant2]));
        self::assertInstanceOf(CropVariantCollection::class, $cropVariantCollection);

        $assertSameValues = function ($expected, $actual) use (&$assertSameValues) {
            if (is_array($expected)) {
                foreach ($expected as $key => $value) {
                    $this->assertArrayHasKey($key, $actual);
                    $assertSameValues($expected[$key], $actual[$key]);
                }
            } else {
                $this->assertSame($expected, $actual);
            }
        };
        // assertSame does not work here, because the fuzz for float is not applied for array values
        $assertSameValues(['default' => $cropVariant1, 'Second' => $cropVariant2], $cropVariantCollection->asArray());
    }

    /**
     * @test
     */
    public function duplicateIdThrowsException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $cropVariant1 = new CropVariant('foo', 'title 1', new Area(0.0, 0.0, 1.0, 1.0));
        $cropVariant2 = new CropVariant('foo', 'title 2', new Area(0.0, 0.0, 0.5, 0.5));
        new CropVariantCollection([$cropVariant1, $cropVariant2]);
    }

    /**
     * @test
     */
    public function createEmptyWorks()
    {
        self::assertTrue(CropVariantCollection::create('')->getCropArea()->isEmpty());
    }

    /**
     * @test
     */
    public function castToStringReturnsJsonArrayOnEmptyInput(): void
    {
        $variants = new CropVariantCollection([]);
        self::assertSame('[]', (string)$variants);
    }
}
