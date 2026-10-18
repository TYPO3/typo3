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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\Element;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Element\ImageManipulationElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ImageManipulationElementTest extends UnitTestCase
{
    public static function cropVariantsProvider(): array
    {
        return [
            'empty crop variants array' => [
                [],
                true,
            ],
            'identical crop variants array' => [
                [
                    'default' => [
                        'title' => 'Title 1',
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                            '3:2' => [
                                'title' => '3:2',
                                'value' => 3 / 2,
                            ],
                        ],
                        'selectedRatio' => 'NaN',
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                    ],
                    'crop2' => [
                        'title' => 'Title 2',
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                            '3:2' => [
                                'title' => '3:2',
                                'value' => 3 / 2,
                            ],
                        ],
                        'selectedRatio' => 'NaN',
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                    ],
                ],
                true,
            ],
            'identical crop variants array random key order' => [
                [
                    'crop2' => [
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                            '3:2' => [
                                'title' => '3:2',
                                'value' => 3 / 2,
                            ],
                        ],
                        'title' => 'Title 2',
                        'selectedRatio' => 'NaN',
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                    ],
                    'default' => [
                        'title' => 'Title 1',
                        'selectedRatio' => 'NaN',
                        'cropArea' => [
                            'width' => 1.0,
                            'height' => 1.0,
                            'x' => 0.0,
                            'y' => 0.0,
                        ],
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                            '3:2' => [
                                'title' => '3:2',
                                'value' => 3 / 2,
                            ],
                        ],
                    ],
                ],
                true,
            ],
            'not matching crop variants array' => [
                [
                    'default' => [
                        'title' => 'Title 1',
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                        ],
                        'selectedRatio' => 'NaN',
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                    ],
                    'crop2' => [
                        'title' => 'Title 2',
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                            '3:2' => [
                                'title' => '3:2',
                                'value' => 3 / 2,
                            ],
                        ],
                        'selectedRatio' => 'NaN',
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                    ],
                ],
                false,
            ],
            'not matching crop variants array 2' => [
                [
                    'default' => [
                        'title' => 'Title 1',
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                            '3:2' => [
                                'title' => '',
                                'value' => 3 / 2,
                            ],
                        ],
                        'selectedRatio' => 'NaN',
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                    ],
                    'crop2' => [
                        'title' => 'Title 2',
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                            '3:2' => [
                                'title' => '3:2',
                                'value' => 3 / 2,
                            ],
                        ],
                        'selectedRatio' => 'NaN',
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                    ],
                ],
                false,
            ],
            'not matching crop variants array with exclude' => [
                [
                    'default' => [
                        'title' => 'Title 1',
                        'excludeFromSync' => 1,
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                        ],
                        'selectedRatio' => 'NaN',
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                    ],
                    'crop2' => [
                        'title' => 'Title 2',
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                            '3:2' => [
                                'title' => '3:2',
                                'value' => 3 / 2,
                            ],
                        ],
                        'selectedRatio' => 'NaN',
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                    ],
                ],
                true,
            ],
            'not matching crop variants array with exclude random key order' => [
                [
                    'crop2' => [
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                        'title' => 'Title 2',
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                            '3:2' => [
                                'title' => '3:2',
                                'value' => 3 / 2,
                            ],
                        ],
                        'selectedRatio' => 'NaN',
                    ],
                    'default' => [
                        'title' => 'Title 1',
                        'excludeFromSync' => 1,
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                        ],
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                        'selectedRatio' => 'NaN',
                    ],
                ],
                true,
            ],
        ];
    }

    #[DataProvider('cropVariantsProvider')]
    #[Test]
    public function checkIfCropVariantsAreEqualWorksCorrecty(
        $cropVariantsArray,
        $expectedResult,
    ): void {
        $mockObject = $this->getAccessibleMock(
            ImageManipulationElement::class,
            null,
            [],
            '',
            false,
        );

        $result = $mockObject->_call('checkIfCropVariantsAreEqual', $cropVariantsArray);

        self::assertEquals(
            $expectedResult,
            $result
        );
    }

    #[Test]
    public function populateConfigurationCompletesPartiallyConfiguredDefaultCropVariant(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'jpg,png';
        $subject = $this->getAccessibleMock(ImageManipulationElement::class, null, [], '', false);

        $result = $subject->_call('populateConfiguration', [
            'cropVariants' => [
                'default' => [
                    'selectedRatio' => '16:9',
                ],
            ],
        ]);

        $defaultCropVariant = $result['cropVariants']['default'];
        self::assertSame('16:9', $defaultCropVariant['selectedRatio']);
        self::assertSame(['16:9', '3:2', '4:3', '1:1', 'NaN'], array_keys($defaultCropVariant['allowedAspectRatios']));
        self::assertSame('LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.default', $defaultCropVariant['title']);
        self::assertSame(['x' => 0.0, 'y' => 0.0, 'width' => 1.0, 'height' => 1.0], $defaultCropVariant['cropArea']);
    }

    #[Test]
    public function populateConfigurationKeepsCustomAspectRatiosOfDefaultCropVariant(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'jpg,png';
        $subject = $this->getAccessibleMock(ImageManipulationElement::class, null, [], '', false);

        $result = $subject->_call('populateConfiguration', [
            'cropVariants' => [
                'default' => [
                    'allowedAspectRatios' => [
                        '2:1' => [
                            'title' => '2:1',
                            'value' => 2.0,
                        ],
                    ],
                    'selectedRatio' => '2:1',
                ],
            ],
        ]);

        self::assertSame(['2:1'], array_keys($result['cropVariants']['default']['allowedAspectRatios']));
    }

    #[Test]
    public function populateConfigurationDoesNotAddDefaultCropVariantToCustomCropVariants(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'jpg,png';
        $subject = $this->getAccessibleMock(ImageManipulationElement::class, null, [], '', false);

        $result = $subject->_call('populateConfiguration', [
            'cropVariants' => [
                'mobile' => [
                    'title' => 'Mobile',
                    'allowedAspectRatios' => [
                        '1:1' => [
                            'title' => '1:1',
                            'value' => 1.0,
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame(['mobile'], array_keys($result['cropVariants']));
    }
}
