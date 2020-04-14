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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class InlineOverrideChildTcaTest extends UnitTestCase
{
    /**
     * @var InlineOverrideChildTca
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new InlineOverrideChildTca();
    }

    /**
     * @test
     */
    public function addDataOverrulesShowitemByGivenOverrideChildTca()
    {
        $input = [
            'inlineParentConfig' => [
                'overrideChildTca' => [
                    'types' => [
                        'aType' => [
                            'showitem' => 'keepMe',
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'keepMe, aField',
                    ],
                    'bType' => [
                        'showitem' => 'keepMe, aField',
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['types']['aType']['showitem'] = 'keepMe';

        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataAddsTypeShowitemByGivenOverrideChildTca()
    {
        $input = [
            'inlineParentConfig' => [
                'overrideChildTca' => [
                    'types' => [
                        'aType' => [
                            'showitem' => 'keepMe',
                        ],
                        'cType' => [
                            'showitem' => 'keepMe',
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'keepMe, aField',
                    ],
                    'bType' => [
                        'showitem' => 'keepMe, aField',
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['types']['aType']['showitem'] = 'keepMe';
        $expected['processedTca']['types']['cType']['showitem'] = 'keepMe';

        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMergesForeignSelectorFieldTcaOverride()
    {
        $input = [
            'inlineParentConfig' => [
                'foreign_selector' => 'uid_local',
                'overrideChildTca' => [
                    'columns' => [
                        'uid_local' => [
                            'label' => 'aDifferentLabel',
                            'config' => [
                                'aGivenSetting' => 'overrideValue',
                                'aNewSetting' => 'anotherNewValue',
                                'appearance' => [
                                    'elementBrowserType' => 'file',
                                    'elementBrowserAllowed' => 'jpg,png'
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'uid_local' => [
                        'label' => 'aLabel',
                        'config' => [
                            'aGivenSetting' => 'aValue',
                            'doNotChangeMe' => 'doNotChangeMe',
                            'appearance' => [
                                'elementBrowserType' => 'db',
                            ],
                        ],
                    ]
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['uid_local'] = [
            'label' => 'aDifferentLabel',
            'config' => [
                'aGivenSetting' => 'overrideValue',
                'doNotChangeMe' => 'doNotChangeMe',
                'appearance' => [
                    'elementBrowserType' => 'file',
                    'elementBrowserAllowed' => 'jpg,png',
                ],
                'aNewSetting' => 'anotherNewValue',
            ],
        ];

        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultValueForChildRecordColumn()
    {
        $input = [
            'inlineParentConfig' => [
                'overrideChildTca' => [
                    'columns' => [
                        'aType' => [
                            'config' => [
                                'default' => '42',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aType' => [
                        'config' => [],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aType']['config']['default'] = '42';

        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForRestrictedField()
    {
        $input = [
            'inlineParentConfig' => [
                'overrideChildTca' => [
                    'columns' => [
                        'pid' => [
                            'config' => [
                                'default' => '42',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aType' => [
                        'config' => [],
                    ],
                ],
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1490371322);
        $this->subject->addData($input);
    }
}
