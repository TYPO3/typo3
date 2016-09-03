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

use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DatabaseEditRowTest extends UnitTestCase
{
    /**
     * @var DatabaseEditRow|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var DatabaseConnection | ObjectProphecy
     */
    protected $dbProphecy;

    protected function setUp()
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
        $this->subject->expects($this->once())->method('getDatabaseRow')->willReturn($resultRow);

        $result = $this->subject->addData($input);

        $this->assertSame($resultRow, $result['databaseRow']);
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
        $this->subject->expects($this->once())->method('getDatabaseRow')->willReturn($resultRow);

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
        $this->subject->expects($this->once())->method('getDatabaseRow')->willReturn([]);

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
        $this->subject->expects($this->once())->method('getDatabaseRow')->willReturn([]);

        try {
            $this->subject->addData($input);
        } catch (DatabaseRecordException $e) {
            $this->assertSame('tt_content', $e->getTableName());
            $this->assertSame(10, $e->getUid());
        }
    }
}
