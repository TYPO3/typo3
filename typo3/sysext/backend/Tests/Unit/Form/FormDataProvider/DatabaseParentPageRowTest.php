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
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DatabaseParentPageRowTest extends UnitTestCase
{
    /**
     * @var DatabaseParentPageRow
     */
    protected $subject;

    /**
     * @var DatabaseConnection | ObjectProphecy
     */
    protected $dbProphecy;

    protected function setUp()
    {
        $this->subject = new DatabaseParentPageRow();

        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();
    }

    /**
     * @test
     */
    public function addDataFetchesParentPageRowOfRecordIfNeighbourGiven()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => -10,
        ];
        $parentPageRow = [
            'uid' => 123,
            'pid' => 321
        ];
        $this->dbProphecy->quoteStr(Argument::cetera())->willReturnArgument(0);
        $this->dbProphecy->exec_SELECTgetSingleRow('*', $input['tableName'], 'uid=10')->willReturn(['pid' => 123]);
        $this->dbProphecy->exec_SELECTgetSingleRow('*', 'pages', 'uid=123')->willReturn($parentPageRow);

        $result = $this->subject->addData($input);

        $this->assertSame($parentPageRow, $result['parentPageRow']);
    }

    /**
     * @test
     */
    public function addDataSetsNeigborRowIfNegativeUidGiven()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => -10,
        ];
        $neigborRow = [
            'uid' => 10,
            'pid' => 321
        ];
        $this->dbProphecy->quoteStr(Argument::cetera())->willReturnArgument(0);
        $this->dbProphecy->exec_SELECTgetSingleRow('*', $input['tableName'], 'uid=10')->willReturn($neigborRow);
        $this->dbProphecy->exec_SELECTgetSingleRow('*', 'pages', 'uid=321')->willReturn([]);

        $result = $this->subject->addData($input);

        $this->assertSame($neigborRow, $result['neighborRow']);
    }

    /**
     * @test
     */
    public function addDataSetsParentPageRowToNullIfParentIsRoot()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => -10,
        ];
        $this->dbProphecy->quoteStr(Argument::cetera())->willReturnArgument(0);
        $this->dbProphecy->exec_SELECTgetSingleRow('*', $input['tableName'], 'uid=10')->willReturn(['pid' => 0]);

        $result = $this->subject->addData($input);

        $this->assertNull($result['parentPageRow']);
    }

    /**
     * @test
     */
    public function addDataSetsParentPageToGivenPageIdIfCommandIsNew()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => 123,
        ];
        $parentPageRow = [
            'uid' => 123,
            'pid' => 321
        ];
        $this->dbProphecy->quoteStr(Argument::cetera())->willReturnArgument(0);
        $this->dbProphecy->exec_SELECTgetSingleRow('*', 'pages', 'uid=123')->willReturn($parentPageRow);

        $result = $this->subject->addData($input);

        $this->assertSame($parentPageRow, $result['parentPageRow']);
    }

    /**
     * @test
     */
    public function addDataSetsParentPageRowOnParentIfCommandIsEdit()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [
                'uid' => 123,
                'pid' => 321
            ],
        ];
        $parentPageRow = [
            'uid' => 321,
            'pid' => 456
        ];
        $this->dbProphecy->quoteStr(Argument::cetera())->willReturnArgument(0);
        $this->dbProphecy->exec_SELECTgetSingleRow('*', 'pages', 'uid=321')->willReturn($parentPageRow);

        $result = $this->subject->addData($input);

        $this->assertSame($parentPageRow, $result['parentPageRow']);
    }
}
