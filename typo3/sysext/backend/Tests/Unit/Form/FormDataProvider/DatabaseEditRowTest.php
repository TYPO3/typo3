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
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordWorkspaceDeletePlaceholderException;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform\MockMySQLPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatabaseEditRowTest extends UnitTestCase
{
    protected DatabaseEditRow&MockObject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(DatabaseEditRow::class)
            ->onlyMethods(['getDatabaseRow'])
            ->getMock();
    }

    #[Test]
    public function addDataRetrievesRecordInformationFromDatabase(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $resultRow = [
            'uid' => 10,
            'pid' => 123,
        ];
        $this->subject->expects($this->once())->method('getDatabaseRow')->willReturn($resultRow);

        $result = $this->subject->addData($input);

        self::assertSame($resultRow, $result['databaseRow']);
    }

    #[Test]
    public function addDataThrowsExceptionIfRetrievedRowHasNoPid(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $resultRow = [
            'uid' => 10,
        ];
        $this->subject->expects($this->once())->method('getDatabaseRow')->willReturn($resultRow);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1437663061);

        $this->subject->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionIfGivenUidIsNotPositive(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => -10,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437656456);

        $this->subject->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionIfDatabaseFetchingReturnsNoRow(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $this->subject->expects($this->once())->method('getDatabaseRow')->willReturn([]);

        $this->expectException(DatabaseRecordException::class);
        $this->expectExceptionCode(1437656081);

        $this->subject->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionDatabaseRecordExceptionWithAdditionalInformationSet(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $this->subject->expects($this->once())->method('getDatabaseRow')->willReturn([]);

        try {
            $this->subject->addData($input);
        } catch (DatabaseRecordException $e) {
            self::assertSame('tt_content', $e->getTableName());
            self::assertSame(10, $e->getUid());
        }
    }

    #[Test]
    public function addDataThrowsWorkspaceDeletePlaceholderExceptionWithDeletePlaceholderRecord(): void
    {
        $connectionMock = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $connectionMock->method('getDatabasePlatform')->willReturn(new MockMySQLPlatform());
        $connectionPoolMock = $this->getMockBuilder(ConnectionPool::class)->disableOriginalConstructor()->getMock();
        $connectionPoolMock->method('getConnectionForTable')->willReturn($connectionMock);
        $connectionPoolMock->method('getConnectionByName')->willReturn($connectionMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        $this->expectException(DatabaseRecordWorkspaceDeletePlaceholderException::class);
        $this->expectExceptionCode(1608658396);
        $GLOBALS['TCA']['tt_content']['ctrl']['versioningWS'] = 1;
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $resultRow = [
            'uid' => 10,
            'pid' => 123,
            't3ver_state' => 2,
        ];
        $this->subject->expects($this->once())->method('getDatabaseRow')->willReturn($resultRow);
        $this->subject->addData($input);
    }

    #[Test]
    public function addDataSkipsDatabaseLookupIfDatabaseRowIsPopulated(): void
    {
        $virtualRow = [
            'uid' => 10,
            'pid' => 123,
            'title' => 'Title of the virtual record',
        ];
        $input = [
            'tableName' => 'virtual_table',
            'command' => 'edit',
            'vanillaUid' => 10,
            'databaseRow' => $virtualRow,
        ];
        $resultRow = $virtualRow;
        $this->subject->expects($this->never())->method('getDatabaseRow');

        $result = $this->subject->addData($input);

        self::assertSame($resultRow, $result['databaseRow']);
    }
}
