<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case
 */
class TcaColumnsProcessFieldLabelsTest extends UnitTestCase
{
    /**
     * @var TcaColumnsProcessFieldLabels
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new TcaColumnsProcessFieldLabels();
    }

    /**
     * @test
     */
    public function addDataKeepsLabelAsIsIfNoOverrideIsGiven()
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'label' => 'foo',
                    ],
                ],
            ],
        ];
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL('foo')->shouldBeCalled()->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $expected = $input;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsLabelFromShowitem()
    {
        $input = [
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
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL('aLabelOverride')->shouldBeCalled()->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $expected = $input;
        $expected['processedTca']['columns']['aField']['label'] = 'aLabelOverride';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsLabelFromPalettesShowitem()
    {
        $input = [
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
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL('aLabelOverride')->shouldBeCalled()->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $expected = $input;
        $expected['processedTca']['columns']['aField']['label'] = 'aLabelOverride';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsLabelFromPageTsConfig()
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
        ];
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL('aLabelOverride')->shouldBeCalled()->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $expected = $input;
        $expected['processedTca']['columns']['aField']['label'] = 'aLabelOverride';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsLabelFromPageTsConfigForSpecificLanguage()
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
        ];
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->lang = 'fr';
        $languageServiceProphecy->sL('aLabelOverride')->shouldBeCalled()->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $expected = $input;
        $expected['processedTca']['columns']['aField']['label'] = 'aLabelOverride';
        $this->assertSame($expected, $this->subject->addData($input));
    }
}
