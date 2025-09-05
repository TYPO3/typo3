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
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatabaseParentPageRowTest extends UnitTestCase
{
    protected DatabaseParentPageRow&MockObject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(DatabaseParentPageRow::class)
            ->onlyMethods(['getDatabaseRow'])
            ->getMock();
    }

    #[Test]
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

        $series = [
            [['tableName' => $input['tableName'], 'uid' => 10], ['pid' => 123]],
            [['tableName' => 'pages', 'uid' => 123], $parentPageRow],
        ];
        $this->subject->expects($this->exactly(2))->method('getDatabaseRow')->willReturnCallback(function (string $tableName, int $uid) use (&$series): array {
            [$expectedArgs, $return] = array_shift($series);
            self::assertSame($expectedArgs['tableName'], $tableName);
            self::assertSame($expectedArgs['uid'], $uid);
            return $return;
        });

        $result = $this->subject->addData($input);

        self::assertSame($parentPageRow, $result['parentPageRow']);
    }

    #[Test]
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

        $series = [
            [['tableName' => $input['tableName'], 'uid' => 10], $neighborRow],
            [['tableName' => 'pages', 'uid' => 321], $parentPageRow],
        ];
        $this->subject->expects($this->exactly(2))->method('getDatabaseRow')->willReturnCallback(function (string $tableName, int $uid) use (&$series): array {
            [$expectedArgs, $return] = array_shift($series);
            self::assertSame($expectedArgs['tableName'], $tableName);
            self::assertSame($expectedArgs['uid'], $uid);
            return $return;
        });

        $result = $this->subject->addData($input);

        self::assertSame($neighborRow, $result['neighborRow']);
    }

    #[Test]
    public function addDataSetsParentPageRowToNullIfParentIsRoot(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => -10,
        ];

        $this->subject->expects($this->once())
            ->method('getDatabaseRow')
            ->with($input['tableName'], 10)
            ->willReturn(['pid' => 0]);

        $result = $this->subject->addData($input);

        self::assertNull($result['parentPageRow']);
    }

    #[Test]
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

        $this->subject->expects($this->once())
            ->method('getDatabaseRow')
            ->with('pages', 123)
            ->willReturn($parentPageRow);

        $result = $this->subject->addData($input);

        self::assertSame($parentPageRow, $result['parentPageRow']);
    }

    #[Test]
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
        $this->subject->expects($this->once())
            ->method('getDatabaseRow')
            ->with('pages', 321)
            ->willReturn($parentPageRow);

        $result = $this->subject->addData($input);

        self::assertSame($parentPageRow, $result['parentPageRow']);
    }

    #[Test]
    public function addDataSetsParentPageRowOnNullWithNew(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => 'NEW123',
            'databaseRow' => [],
        ];

        $this->subject->expects($this->never())->method('getDatabaseRow');

        $result = $this->subject->addData($input);

        self::assertNull($result['parentPageRow']);
    }

    #[Test]
    public function addDataSetsParentPageRowOnNullWithZero(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => 0,
            'databaseRow' => [],
        ];

        $this->subject->expects($this->never())->method('getDatabaseRow');

        $result = $this->subject->addData($input);

        self::assertNull($result['parentPageRow']);
    }
}
