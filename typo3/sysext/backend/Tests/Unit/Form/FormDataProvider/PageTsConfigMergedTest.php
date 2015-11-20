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

use TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class PageTsConfigMergedTest extends UnitTestCase
{
    /**
     * @var PageTsConfigMerged
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new PageTsConfigMerged();
    }

    /**
     * @test
     */
    public function addDataSetsMergedTsConfigToTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
            '' => 'aType',
            'pageTsConfig' => [
                'aSetting' => 'aValue',
            ],
        ];
        $expected = $input;
        $expected['pageTsConfig'] = $input['pageTsConfig'];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsTableSpecificConfigurationWithoutType()
    {
        $input = [
            'tableName' => 'aTable',
            'recordTypeValue' => 'aType',
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'disabled' => 1,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['pageTsConfig'] = $input['pageTsConfig'];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMergesTypeSpecificConfiguration()
    {
        $input = [
            'tableName' => 'aTable',
            'recordTypeValue' => 'aType',
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'types.' => [
                                'aType.' => [
                                    'disabled' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['pageTsConfig'] = [
            'TCEFORM.' => [
                'aTable.' => [
                    'aField.' => [
                        'disabled' => 1,
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataTypeSpecificConfigurationOverwritesMainConfiguration()
    {
        $input = [
            'tableName' => 'aTable',
            'recordTypeValue' => 'aType',
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'disabled' => 0,
                            'types.' => [
                                'aType.' => [
                                    'disabled' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['pageTsConfig'] = [
            'TCEFORM.' => [
                'aTable.' => [
                    'aField.' => [
                        'disabled' => 1,
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $this->subject->addData($input));
    }
}
