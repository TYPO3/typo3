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

use TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PageTsConfigMergedTest extends UnitTestCase
{
    protected PageTsConfigMerged $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new PageTsConfigMerged();
    }

    /**
     * @test
     */
    public function addDataSetsMergedTsConfigToTsConfig(): void
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
        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsTableSpecificConfigurationWithoutType(): void
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
        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMergesTypeSpecificConfiguration(): void
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
        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataTypeSpecificConfigurationOverwritesMainConfiguration(): void
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
        self::assertSame($expected, $this->subject->addData($input));
    }
}
