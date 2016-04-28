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

use Prophecy\Argument;
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
     * @var DatabaseEditRow
     */
    protected $subject;

    /**
     * @var DatabaseConnection | ObjectProphecy
     */
    protected $dbProphecy;

    protected function setUp()
    {
        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();

        $this->subject = new DatabaseEditRow();
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
        $this->dbProphecy->quoteStr($input['tableName'], $input['tableName'])->willReturn($input['tableName']);
        $this->dbProphecy->exec_SELECTgetSingleRow('*', 'tt_content', 'uid=' . $input['vanillaUid'])->willReturn($resultRow);
        $this->dbProphecy->exec_SELECTgetSingleRow(Argument::cetera())->willReturn([]);

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
        $this->dbProphecy->quoteStr($input['tableName'], $input['tableName'])->willReturn($input['tableName']);
        $this->dbProphecy->exec_SELECTgetSingleRow('*', 'tt_content', 'uid=' . $input['vanillaUid'])->willReturn($resultRow);

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1437663061);

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

        $this->setExpectedException(\InvalidArgumentException::class, $this->anything(), 1437656456);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfNoRecordForEditingCouldBeRetrievedFromDatabase()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];

        $this->setExpectedException(\RuntimeException::class, $this->anything(), 1437655862);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfDatabaseFetchingReturnsFalse()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $this->dbProphecy->quoteStr(Argument::cetera())->willReturn($input['tableName']);
        $this->dbProphecy->exec_SELECTgetSingleRow(Argument::cetera())->willReturn(false);

        $this->setExpectedException(DatabaseRecordException::class, $this->anything(), 1437656081);

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
        $this->dbProphecy->quoteStr(Argument::cetera())->willReturn($input['tableName']);
        $this->dbProphecy->exec_SELECTgetSingleRow(Argument::cetera())->willReturn(false);

        try {
            $this->subject->addData($input);
        } catch (DatabaseRecordException $e) {
            $this->assertSame('tt_content', $e->getTableName());
            $this->assertSame(10, $e->getUid());
        }
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfDatabaseFetchingReturnsInvalidRowResultData()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $this->dbProphecy->quoteStr(Argument::cetera())->willReturn($input['tableName']);
        $this->dbProphecy->exec_SELECTgetSingleRow(Argument::cetera())->willReturn('invalid result data');

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1437656323);

        $this->subject->addData($input);
    }
}
