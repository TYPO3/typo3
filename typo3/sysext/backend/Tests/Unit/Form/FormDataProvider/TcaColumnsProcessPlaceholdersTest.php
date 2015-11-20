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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class TcaColumnsProcessPlaceholdersTest extends UnitTestCase
{
    /**
     * @var TcaColumnsProcessPlaceholders
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new TcaColumnsProcessPlaceholders();
    }

    /**
     * @test
     */
    public function addDataRegistersPlaceholderColumns()
    {
        $input = [
            'columnsToProcess' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|anotherField'
                        ],
                    ],
                ],
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['anotherField'];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersFirstPlaceholderColumn()
    {
        $input = [
            'columnsToProcess' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|uid_local|metadata|title'
                        ],
                    ],
                ],
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['uid_local'];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersAlternativeLabelColumnn()
    {
        $input = [
            'columnsToProcess' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => 'A simple placeholder'
                        ],
                    ],
                ],
            ]
        ];

        $expected = $input;
        $this->assertSame($expected, $this->subject->addData($input));
    }
}
