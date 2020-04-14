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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DatabaseParentPageRowTest extends UnitTestCase
{
    /**
     * @var DatabaseParentPageRow|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(DatabaseParentPageRow::class)
            ->setMethods(['getDatabaseRow'])
            ->getMock();
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

        $this->subject->expects(self::at(0))
            ->method('getDatabaseRow')
            ->with($input['tableName'], 10)
            ->willReturn(['pid' => 123]);

        $this->subject->expects(self::at(1))
            ->method('getDatabaseRow')
            ->with('pages', 123)
            ->willReturn($parentPageRow);

        $result = $this->subject->addData($input);

        self::assertSame($parentPageRow, $result['parentPageRow']);
    }

    /**
     * @test
     */
    public function addDataSetsNeighborRowIfNegativeUidGiven()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => -10,
        ];
        $neighborRow = [
            'uid' => 10,
            'pid' => 321
        ];
        $parentPageRow = [
            'uid' => 123,
            'pid' => 321
        ];
        $this->subject->expects(self::at(0))
            ->method('getDatabaseRow')
            ->with($input['tableName'], 10)
            ->willReturn($neighborRow);

        $this->subject->expects(self::at(1))
            ->method('getDatabaseRow')
            ->with('pages', 321)
            ->willReturn($parentPageRow);

        $result = $this->subject->addData($input);

        self::assertSame($neighborRow, $result['neighborRow']);
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

        $this->subject->expects(self::once())
            ->method('getDatabaseRow')
            ->with($input['tableName'], 10)
            ->willReturn(['pid' => 0]);

        $result = $this->subject->addData($input);

        self::assertNull($result['parentPageRow']);
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

        $this->subject->expects(self::once())
            ->method('getDatabaseRow')
            ->with('pages', 123)
            ->willReturn($parentPageRow);

        $result = $this->subject->addData($input);

        self::assertSame($parentPageRow, $result['parentPageRow']);
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
        $this->subject->expects(self::once())
            ->method('getDatabaseRow')
            ->with('pages', 321)
            ->willReturn($parentPageRow);

        $result = $this->subject->addData($input);

        self::assertSame($parentPageRow, $result['parentPageRow']);
    }
}
