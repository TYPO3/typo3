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

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaUuid;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaUuidTest extends UnitTestCase
{
    #[Test]
    public function addDataDoesOnlyHandleTypeUuid(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame('', (new TcaUuid())->addData($input)['databaseRow']['aField']);
    }

    #[Test]
    public function addDataDoesNotHandleFieldsWithValidUuidValue(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'b3190536-1431-453e-afbb-25b8c5022513',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'uuid',
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame('b3190536-1431-453e-afbb-25b8c5022513', (new TcaUuid())->addData($input)['databaseRow']['aField']);
    }

    #[Test]
    public function addDataCreatesValidUuidValueForInvalidUuid(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '_-invalid-_',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'uuid',
                        ],
                    ],
                ],
            ],
        ];

        self::assertFalse(Uuid::isValid($input['databaseRow']['aField']));
        self::assertTrue(Uuid::isValid((new TcaUuid())->addData($input)['databaseRow']['aField']));
    }

    #[Test]
    public function addDataCreatesValidUuidValueForEmptyField(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'uuid',
                        ],
                    ],
                ],
            ],
        ];

        self::assertFalse(Uuid::isValid($input['databaseRow']['aField']));
        self::assertTrue(Uuid::isValid((new TcaUuid())->addData($input)['databaseRow']['aField']));
    }

    #[Test]
    public function addDataDoesNotCreateUuidValueOnRequiredFalse(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'uuid',
                            'required' => false,
                        ],
                    ],
                ],
            ],
        ];

        self::assertEmpty((new TcaUuid())->addData($input)['databaseRow']['aField']);
    }

    #[Test]
    public function addDataCreatesValidUuidValueWithDefinedVersion(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'uuid',
                        ],
                    ],
                ],
            ],
        ];

        $input['processedTca']['columns']['aField']['config']['version'] = 6;
        self::assertEquals(6, (int)(new TcaUuid())->addData($input)['databaseRow']['aField'][14]);

        $input['processedTca']['columns']['aField']['config']['version'] = 7;
        self::assertEquals(7, (int)(new TcaUuid())->addData($input)['databaseRow']['aField'][14]);

        $input['processedTca']['columns']['aField']['config']['version'] = 4;
        self::assertEquals(4, (int)(new TcaUuid())->addData($input)['databaseRow']['aField'][14]);

        $input['processedTca']['columns']['aField']['config']['version'] = 12345678; // Defaults to 4
        self::assertEquals(4, (int)(new TcaUuid())->addData($input)['databaseRow']['aField'][14]);

        unset($input['processedTca']['columns']['aField']['config']['version']); // Defaults to 4
        self::assertEquals(4, (int)(new TcaUuid())->addData($input)['databaseRow']['aField'][14]);
    }
}
