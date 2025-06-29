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

namespace TYPO3\CMS\Backend\Tests\Unit\RecordList\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Backend\RecordList\Event\AfterRecordListRowPreparedEvent;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AfterRecordListRowPreparedEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $table = 'tt_content';
        $record = new RawRecord(1, 1, ['CType' => 'text'], new ComputedProperties(), $table);
        $data = ['header' => 'Test Header', '__label' => 'Test Label'];
        $recordList = $this->createMock(DatabaseRecordList::class);
        $recTitle = 'Record Title';
        $lockInfo = false;
        $tagAttributes = ['class' => 'my-class', 'data-table' => 'tt_content'];

        $event = new AfterRecordListRowPreparedEvent(
            $table,
            $record,
            $data,
            $recordList,
            $recTitle,
            $lockInfo,
            $tagAttributes,
        );

        self::assertSame($table, $event->getTable());
        self::assertSame($record, $event->getRecord());
        self::assertSame($data, $event->getData());
        self::assertSame($recordList, $event->getRecordList());
        self::assertSame($recTitle, $event->getRecTitle());
        self::assertSame($lockInfo, $event->getLockInfo());
        self::assertSame($tagAttributes, $event->getTagAttributes());
    }

    #[Test]
    public function setDataModifiesData(): void
    {
        $table = 'tt_content';
        $record = new RawRecord(1, 1, ['CType' => 'text'], new ComputedProperties(), $table);
        $data = ['header' => 'Test Header'];
        $recordList = $this->createMock(DatabaseRecordList::class);

        $event = new AfterRecordListRowPreparedEvent(
            $table,
            $record,
            $data,
            $recordList,
            null,
            false,
            [],
        );

        $modifiedData = ['header' => 'Modified Header', '__label' => 'New Label'];
        $event->setData($modifiedData);

        self::assertSame($modifiedData, $event->getData());
    }

    #[Test]
    public function setTagAttributesModifiesTagAttributes(): void
    {
        $table = 'tt_content';
        $record = new RawRecord(1, 1, ['CType' => 'text'], new ComputedProperties(), $table);
        $tagAttributes = ['class' => 'original'];
        $recordList = $this->createMock(DatabaseRecordList::class);

        $event = new AfterRecordListRowPreparedEvent(
            $table,
            $record,
            [],
            $recordList,
            null,
            false,
            $tagAttributes,
        );

        $modifiedTagAttributes = ['class' => 'modified', 'data-custom' => 'value'];
        $event->setTagAttributes($modifiedTagAttributes);

        self::assertSame($modifiedTagAttributes, $event->getTagAttributes());
    }

    #[Test]
    public function lockInfoCanBeArray(): void
    {
        $table = 'tt_content';
        $record = new RawRecord(1, 1, ['CType' => 'text'], new ComputedProperties(), $table);
        $lockInfo = ['msg' => 'Locked by admin', 'user' => 'admin'];
        $recordList = $this->createMock(DatabaseRecordList::class);

        $event = new AfterRecordListRowPreparedEvent(
            $table,
            $record,
            [],
            $recordList,
            null,
            $lockInfo,
            [],
        );

        self::assertSame($lockInfo, $event->getLockInfo());
    }
}
