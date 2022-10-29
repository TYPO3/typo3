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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DatabaseRowDefaultValuesTest extends UnitTestCase
{
    protected DatabaseRowDefaultValues $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new DatabaseRowDefaultValues();
    }

    /**
     * @test
     */
    public function addDataKeepsExistingValue(): void
    {
        $input = [
            'databaseRow' => [
                'aDefinedField' => 'aValue',
            ],
            'processedTca' => [
                'columns' => [
                    'aDefinedField' => [],
                ],
            ],
        ];
        $expected = $input;
        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsExistingNullValueWithEvalNull(): void
    {
        $input = [
            'databaseRow' => [
                'aField' => null,
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'nullable' => true,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsNullValueWithDefaultNullForNewRecord(): void
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'nullable' => true,
                            'default' => null,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = null;
        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultValueIfEvalNullIsSet(): void
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'nullable' => true,
                            'default' => 'foo',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = 'foo';
        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultValueIsSet(): void
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'default' => 'foo',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = 'foo';
        self::assertSame($expected, $this->subject->addData($input));
    }
}
