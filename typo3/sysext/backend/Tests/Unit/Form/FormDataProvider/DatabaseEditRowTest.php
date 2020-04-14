<?php

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

use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DatabaseEditRowTest extends UnitTestCase
{
    /**
     * @var DatabaseEditRow|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(DatabaseEditRow::class)
            ->setMethods(['getDatabaseRow'])
            ->getMock();
    }

    /**
     * @test
     */
    public function addDataRetrievesRecordInformationFromDatabase()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $resultRow = [
            'uid' => 10,
            'pid' => 123
        ];
        $this->subject->expects(self::once())->method('getDatabaseRow')->willReturn($resultRow);

        $result = $this->subject->addData($input);

        self::assertSame($resultRow, $result['databaseRow']);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfRetrievedRowHasNoPid()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $resultRow = [
            'uid' => 10,
        ];
        $this->subject->expects(self::once())->method('getDatabaseRow')->willReturn($resultRow);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1437663061);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfGivenUidIsNotPositive()
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

    /**
     * @test
     */
    public function addDataThrowsExceptionIfDatabaseFetchingReturnsNoRow()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $this->subject->expects(self::once())->method('getDatabaseRow')->willReturn([]);

        $this->expectException(DatabaseRecordException::class);
        $this->expectExceptionCode(1437656081);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionDatabaseRecordExceptionWithAdditionalInformationSet()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $this->subject->expects(self::once())->method('getDatabaseRow')->willReturn([]);

        try {
            $this->subject->addData($input);
        } catch (DatabaseRecordException $e) {
            self::assertSame('tt_content', $e->getTableName());
            self::assertSame(10, $e->getUid());
        }
    }

    /**
     * @test
     */
    public function addDataSkipsDatabaseLookupIfDatabaseRowIsPopulated()
    {
        $virtualRow = [
            'uid' => 10,
            'pid' => 123,
            'title' => 'Title of the virtual record'
        ];
        $input = [
            'tableName' => 'virtual_table',
            'command' => 'edit',
            'vanillaUid' => 10,
            'databaseRow' => $virtualRow
        ];
        $resultRow = $virtualRow;
        $this->subject->expects(self::never())->method('getDatabaseRow');

        $result = $this->subject->addData($input);

        self::assertSame($resultRow, $result['databaseRow']);
    }
}
