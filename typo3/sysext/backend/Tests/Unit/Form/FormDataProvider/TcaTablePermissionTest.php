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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaTablePermission;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaTablePermissionTest extends UnitTestCase
{
    #[Test]
    public function addDataThrowsExceptionOnMissingSelectFieldName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1720028589);
        (new TcaTablePermission())->addData([
            'command' => 'new',
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'table_a,table_b,table_c',
                'bField' => 'table_d',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'tablePermission',
                            'selectFieldName' => 'nonExistingField',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public static function resultArrayDataProvider(): \Generator
    {
        yield 'Only handle TCA type "tablepermissions" records' => [
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
                    'aField' => '',
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
        yield 'Merges permissions from 2 columns' => [
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => 'table_a,table_b,table_c',
                    'bField' => 'table_d',
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'select',
                                'renderType' => 'tablePermission',
                                'selectFieldName' => 'bField',
                            ],
                        ],
                        'bField' => [
                            'config' => [
                                'type' => 'passthrough',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => [
                        'modify' => ['table_a', 'table_b', 'table_c'],
                        'select' => ['table_d'],
                    ],
                    'bField' => 'table_d',
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'select',
                                'renderType' => 'tablePermission',
                                'selectFieldName' => 'bField',
                            ],
                        ],
                        'bField' => [
                            'config' => [
                                'type' => 'passthrough',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Makes list unique' => [
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => 'table_a,table_a',
                    'bField' => 'table_b,table_b,table_a',
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'select',
                                'renderType' => 'tablePermission',
                                'selectFieldName' => 'bField',
                            ],
                        ],
                        'bField' => [
                            'config' => [
                                'type' => 'passthrough',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'command' => 'new',
                'tableName' => 'aTable',
                'databaseRow' => [
                    'aField' => [
                        'modify' => ['table_a'],
                        'select' => ['table_b', 'table_a'],
                    ],
                    'bField' => 'table_b,table_b,table_a',
                ],
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'select',
                                'renderType' => 'tablePermission',
                                'selectFieldName' => 'bField',
                            ],
                        ],
                        'bField' => [
                            'config' => [
                                'type' => 'passthrough',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('resultArrayDataProvider')]
    #[Test]
    public function addDataDoesHandleTablePermissionsRecords(array $input, array $expected): void
    {
        $GLOBALS['TCA']['aTable']['columns']['bField'] = [];
        self::assertSame($expected, (new TcaTablePermission())->addData($input));
    }
}
