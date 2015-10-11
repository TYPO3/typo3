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
use TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverruleTypesArray;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * InlineOverruleTypesArray Test file
 */
class InlineOverruleTypesArrayTest extends UnitTestCase
{

    /**
     * @var InlineOverruleTypesArray
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new InlineOverruleTypesArray();
    }

    /**
     * @test
     */
    public function addDataOverrulesShowitemsByGivenInlineOverruleTypes()
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
    public function addDataAddsTypeShowitemsByGivenInlineOverruleTypes()
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
}
