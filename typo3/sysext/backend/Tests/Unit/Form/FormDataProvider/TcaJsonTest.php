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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaJson;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaJsonTest extends UnitTestCase
{
    public static function resultArrayDataProvider(): \Generator
    {
        yield 'Only handle new records' => [
            [
                'command' => 'edit',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => '{"foo":"bar"}',
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'json',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'command' => 'edit',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => '{"foo":"bar"}',
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'json',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Only handle TCA type "json" records' => [
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => '{"foo":"bar"}',
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => '{"foo":"bar"}',
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Only handles string values' => [
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => ['foo' => 'bar'],
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'json',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => ['foo' => 'bar'],
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'json',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'String values are properly decoded' => [
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => '{"foo":"bar"}',
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'json',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => ['foo' => 'bar'],
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'json',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Invalid values are handled properly' => [
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => '_-invalid-_',
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'json',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => [],
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'json',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Initialize empty values' => [
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => '',
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'json',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => [],
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'json',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider resultArrayDataProvider
     */
    public function addDataDoesHandleJsonRecords(array $input, array $expected): void
    {
        self::assertSame($expected, (new TcaJson())->addData($input));
    }
}
