<?php
namespace typo3\sysext\backend\Tests\Unit\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * InlineOverrideChildTca Test file
 */
class InlineOverrrideChildTcaTest extends UnitTestCase
{
    /**
     * @var InlineOverrideChildTca
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new InlineOverrideChildTca();
    }

    /**
     * @test
     */
    public function addDataOverrulesShowitemByGivenInlineOverruleTypes()
    {
        $input = [
            'inlineParentConfig' => [
                'foreign_types' => [
                    'aType' => [
                        'showitem' => 'keepMe',
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

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataAddsTypeShowitemByGivenInlineOverruleTypes()
    {
        $input = [
            'inlineParentConfig' => [
                'foreign_types' => [
                    'aType' => [
                        'showitem' => 'keepMe',
                    ],
                    'cType' => [
                        'showitem' => 'keepMe',
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

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMergesForeignSelectorFieldTcaOverride()
    {
        $input = [
            'inlineParentConfig' => [
                'foreign_selector' => 'uid_local',
                'foreign_selector_fieldTcaOverride' => [
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

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultValueForChildRecordColumn()
    {
        $GLOBALS['TCA']['aTable']['columns']['aType'] = [];
        $input = [
            'inlineParentConfig' => [
                'foreign_table' => 'aTable',
                'foreign_record_defaults' => [
                    'aType' => '42',
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

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataIgnoresDefaultValueForRestrictedField()
    {
        $GLOBALS['TCA']['aTable']['columns']['pid'] = [];
        $input = [
            'inlineParentConfig' => [
                'foreign_table' => 'aTable',
                'foreign_record_defaults' => [
                    'pid' => '42',
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

        $this->assertSame($expected, $this->subject->addData($input));
    }
}
