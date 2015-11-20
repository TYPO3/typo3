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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class TcaColumnsOverridesTest extends UnitTestCase
{
    /**
     * @var TcaColumnsOverrides
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new TcaColumnsOverrides();
    }

    /**
     * @test
     */
    public function addDataRemovesGivenColumnsOverrides()
    {
        $input = [
            'recordTypeValue' => 'foo',
            'processedTca' => [
                'columns' => [],
                'types' => [
                    'foo' => [
                        'showitem' => [],
                        'columnsOverrides' => [],
                    ],
                ],
            ],
        ];

        $expected = $input;
        unset($expected['processedTca']['types']['foo']['columnsOverrides']);

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMergesColumnsOverridesIntoColumns()
    {
        $input = [
            'recordTypeValue' => 'foo',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'aConfig' => 'aValue',
                        'anotherConfig' => 'anotherValue',
                    ],
                ],
                'types' => [
                    'foo' => [
                        'showitem' => [],
                        'columnsOverrides' => [
                            'aField' => [
                                'aConfig' => 'aDifferentValue',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['aConfig'] = 'aDifferentValue';
        unset($expected['processedTca']['types']['foo']['columnsOverrides']);

        $this->assertEquals($expected, $this->subject->addData($input));
    }
}
