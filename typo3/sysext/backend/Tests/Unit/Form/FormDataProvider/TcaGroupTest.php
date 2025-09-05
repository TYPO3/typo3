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
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaGroupTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function addDataReturnsFieldUnchangedIfFieldIsNotTypeGroup(): void
    {
        $input = [
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'foo',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        self::assertSame($expected, (new TcaGroup())->addData($input));
    }

    #[Test]
    public function addDataSetsDatabaseData(): void
    {
        $aFieldConfig = [
            'type' => 'group',
            'MM' => 'mmTableName',
            'allowed' => 'aForeignTable',
            'maxitems' => 99999,
        ];
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => 42,
                'aField' => '1,2',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => $aFieldConfig,
                    ],
                ],
            ],
        ];

        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);

        $clipboardMock = $this->createMock(Clipboard::class);
        GeneralUtility::addInstance(Clipboard::class, $clipboardMock);
        $clipboardMock->expects($this->atLeastOnce())->method('initializeClipboard');
        $clipboardMock->expects($this->atLeastOnce())->method('elFromTable')->with('aForeignTable')->willReturn([]);

        $relationHandlerMock = $this->createMock(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerMock);
        $relationHandlerMock->expects($this->atLeastOnce())->method('start')
            ->with('1,2', 'aForeignTable', 'mmTableName', 42, 'aTable', $aFieldConfig);
        $relationHandlerMock->expects($this->atLeastOnce())->method('getFromDB');
        $relationHandlerMock->expects($this->atLeastOnce())->method('processDeletePlaceholder');
        $relationHandlerMock->expects($this->atLeastOnce())->method('getResolvedItemArray')->willReturn([
            [
                'table' => 'aForeignTable',
                'uid' => 1,
                'record' => [
                    'uid' => 1,
                ],
            ],
            [
                'table' => 'aForeignTable',
                'uid' => 2,
                'record' => [
                    'uid' => 2,
                ],
            ],
        ]);

        $expected = $input;
        $expected['databaseRow']['aField'] = [
            [
                'table' => 'aForeignTable',
                'uid' => 1,
                'title' => '',
                'row' => [
                    'uid' => 1,
                ],
            ],
            [
                'table' => 'aForeignTable',
                'uid' => 2,
                'title' => '',
                'row' => [
                    'uid' => 2,
                ],
            ],
        ];
        $expected['processedTca']['columns']['aField']['config']['clipboardElements'] = [];

        self::assertSame($expected, (new TcaGroup())->addData($input));
    }
}
