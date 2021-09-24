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

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DatabaseParentPageRowTest extends UnitTestCase
{
    /**
     * @var DatabaseParentPageRow|MockObject
     */
    protected MockObject $subject;

    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(DatabaseParentPageRow::class)
            ->onlyMethods(['getDatabaseRow'])
            ->getMock();
    }

    /**
     * @test
     */
    public function addDataFetchesParentPageRowOfRecordIfNeighbourGiven(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => -10,
        ];
        $parentPageRow = [
            'uid' => 123,
            'pid' => 321,
        ];

        $this->subject->expects(self::exactly(2))
            ->method('getDatabaseRow')
            ->withConsecutive([$input['tableName'], 10], ['pages', 123])
            ->willReturnOnConsecutiveCalls(['pid' => 123], $parentPageRow);

        $result = $this->subject->addData($input);

        self::assertSame($parentPageRow, $result['parentPageRow']);
    }

    /**
     * @test
     */
    public function addDataSetsNeighborRowIfNegativeUidGiven(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => -10,
        ];
        $neighborRow = [
            'uid' => 10,
            'pid' => 321,
        ];
        $parentPageRow = [
            'uid' => 123,
            'pid' => 321,
        ];
        $this->subject->expects(self::exactly(2))
            ->method('getDatabaseRow')
            ->withConsecutive([$input['tableName'], 10], ['pages', 321])
            ->willReturnOnConsecutiveCalls($neighborRow, $parentPageRow);

        $result = $this->subject->addData($input);

        self::assertSame($neighborRow, $result['neighborRow']);
    }

    /**
     * @test
     */
    public function addDataSetsParentPageRowToNullIfParentIsRoot(): void
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
    public function addDataSetsParentPageToGivenPageIdIfCommandIsNew(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => 123,
        ];
        $parentPageRow = [
            'uid' => 123,
            'pid' => 321,
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
    public function addDataSetsParentPageRowOnParentIfCommandIsEdit(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $parentPageRow = [
            'uid' => 321,
            'pid' => 456,
        ];
        $this->subject->expects(self::once())
            ->method('getDatabaseRow')
            ->with('pages', 321)
            ->willReturn($parentPageRow);

        $result = $this->subject->addData($input);

        self::assertSame($parentPageRow, $result['parentPageRow']);
    }
}
