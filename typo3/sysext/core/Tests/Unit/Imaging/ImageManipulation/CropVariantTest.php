<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Imaging\ImageManipulation;

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

use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariant;
use TYPO3\CMS\Core\Imaging\ImageManipulation\InvalidConfigurationException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CropVariantTest extends UnitTestCase
{
    /**
     * @var array
     */
    private static $tca = [
        'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.default',
        'cropArea' => [
            'x' => 0.0,
            'y' => 0.0,
            'width' => 1.0,
            'height' => 1.0,
        ],
        'allowedAspectRatios' => [
            '16:9' => [
                'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.16_9',
                'value' => 1.777777777777777
            ],
            '4:3' => [
                'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.4_3',
                'value' => 1.333333333333333
            ],
            '1:1' => [
                'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.1_1',
                'value' => 1.0
            ],
            'free' => [
                'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.free',
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

    private static $expectedConfig = [];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$expectedConfig = array_merge(['id' => 'default'], self::$tca);
        foreach (self::$expectedConfig['allowedAspectRatios'] as $id => &$allowedAspectRatio) {
            $allowedAspectRatio = array_merge(['id' => $id], $allowedAspectRatio);
        }
    }

    /**
     * @test
     */
    public function createFromTcaWorks()
    {
        $cropVariant = CropVariant::createFromConfiguration(self::$expectedConfig['id'], self::$tca);
        $this->assertInstanceOf(CropVariant::class, $cropVariant);
        $this->assertSame(self::$expectedConfig, $cropVariant->asArray());
    }

    /**
     * @test
     */
    public function selectedRatioCanBeNull()
    {
        $tca = self::$tca;
        unset($tca['selectedRatio']);
        $this->assertInstanceOf(CropVariant::class, CropVariant::createFromConfiguration(self::$expectedConfig['id'], $tca));
    }

    /**
     * @test
     */
    public function throwsExceptionOnTypeMismatchInRatio()
    {
        $tca = self::$tca;
        $this->expectException(InvalidConfigurationException::class);
        $tca['allowedAspectRatios'][0]['value'] = '1.77777777';
        CropVariant::createFromConfiguration(self::$expectedConfig['id'], $tca);
    }
}
