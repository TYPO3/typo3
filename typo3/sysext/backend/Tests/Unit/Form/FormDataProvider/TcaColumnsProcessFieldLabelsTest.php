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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaColumnsProcessFieldLabelsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addDataKeepsLabelAsIsIfNoOverrideIsGiven(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'label' => 'foo',
                    ],
                ],
            ],
            'recordTypeValue' => 'aType',
        ];
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->expects(self::atLeastOnce())->method('sL')->with('foo')->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceMock;

        $expected = $input;
        self::assertSame($expected, (new TcaColumnsProcessFieldLabels())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsLabelFromShowitem(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'label' => 'origLabel',
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'aField;aLabelOverride',
                    ],
                ],
            ],
            'recordTypeValue' => 'aType',
        ];
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->expects(self::atLeastOnce())->method('sL')->with('aLabelOverride')->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceMock;

        $expected = $input;
        $expected['processedTca']['columns']['aField']['label'] = 'aLabelOverride';
        self::assertSame($expected, (new TcaColumnsProcessFieldLabels())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsLabelFromPalettesShowitem(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'label' => 'origLabel',
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => '--palette--;;aPalette',
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'aField;aLabelOverride',
                    ],
                ],
            ],
            'recordTypeValue' => 'aType',
        ];
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->expects(self::atLeastOnce())->method('sL')->with('aLabelOverride')->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceMock;

        $expected = $input;
        $expected['processedTca']['columns']['aField']['label'] = 'aLabelOverride';
        self::assertSame($expected, (new TcaColumnsProcessFieldLabels())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsLabelFromPageTsConfig(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'label' => 'origLabel',
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'label' => 'aLabelOverride',
                        ],
                    ],
                ],
            ],
            'recordTypeValue' => 'aType',
        ];
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->expects(self::atLeastOnce())->method('sL')->with('aLabelOverride')->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceMock;

        $expected = $input;
        $expected['processedTca']['columns']['aField']['label'] = 'aLabelOverride';
        self::assertSame($expected, (new TcaColumnsProcessFieldLabels())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsLabelFromPageTsConfigForSpecificLanguage(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'label' => 'origLabel',
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'label.' => [
                                'fr' => 'aLabelOverride',
                            ],
                        ],
                    ],
                ],
            ],
            'recordTypeValue' => 'aType',
        ];
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->expects(self::atLeastOnce())->method('sL')->with('aLabelOverride')->willReturnArgument(0);
        $languageService = $languageServiceMock;
        $languageService->lang = 'fr';
        $GLOBALS['LANG'] = $languageService;

        $expected = $input;
        $expected['processedTca']['columns']['aField']['label'] = 'aLabelOverride';
        self::assertSame($expected, (new TcaColumnsProcessFieldLabels())->addData($input));
    }
}
