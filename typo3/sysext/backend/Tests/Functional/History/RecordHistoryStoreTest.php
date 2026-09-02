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

namespace TYPO3\CMS\Backend\Tests\Functional\History;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordHistoryStoreTest extends FunctionalTestCase
{
    private RecordHistoryStore $subject;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new RecordHistoryStore();
    }

    private function getRecordCountByCorrelationId(CorrelationId $correlationId): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_history');
        return (int)$queryBuilder
            ->count('uid')
            ->from('sys_history')
            ->where($queryBuilder->expr()->eq('correlation_id', $queryBuilder->createNamedParameter((string)$correlationId)))
            ->executeQuery()
            ->fetchOne();
    }

    #[Test]
    public function addRecordAddsARecordToTheDatabase(): void
    {
        $correlationId = CorrelationId::forSubject('092a640c-bd8c-490d-b993-ed4bcef1a1f2');
        self::assertSame(0, $this->getRecordCountByCorrelationId($correlationId));
        $this->subject->addRecord('foo', 1, [], $correlationId);
        self::assertSame(1, $this->getRecordCountByCorrelationId($correlationId));
    }

    #[Test]
    public function modifyRecordAddsARecordToTheDatabase(): void
    {
        $correlationId = CorrelationId::forSubject('058f117c-5e21-4222-b308-085fc1113604');
        self::assertSame(0, $this->getRecordCountByCorrelationId($correlationId));
        $this->subject->modifyRecord('foo', 1, [], $correlationId);
        self::assertSame(1, $this->getRecordCountByCorrelationId($correlationId));
    }

    #[Test]
    public function deleteRecordAddsARecordToTheDatabase(): void
    {
        $correlationId = CorrelationId::forSubject('e1a2ea91-fe2f-4a01-b50b-5c2924a27568');
        self::assertSame(0, $this->getRecordCountByCorrelationId($correlationId));
        $this->subject->deleteRecord('foo', 1, $correlationId);
        self::assertSame(1, $this->getRecordCountByCorrelationId($correlationId));
    }

    #[Test]
    public function undeleteRecordAddsARecordToTheDatabase(): void
    {
        $correlationId = CorrelationId::forSubject('ab902256-56f2-43bd-b857-f7a0b974e9db');
        self::assertSame(0, $this->getRecordCountByCorrelationId($correlationId));
        $this->subject->undeleteRecord('foo', 1, $correlationId);
        self::assertSame(1, $this->getRecordCountByCorrelationId($correlationId));
    }

    #[Test]
    public function moveRecordAddsARecordToTheDatabase(): void
    {
        $correlationId = CorrelationId::forSubject('9d806d3a-1d7a-4e62-816f-9fa1a1b3fe5b');
        self::assertSame(0, $this->getRecordCountByCorrelationId($correlationId));
        $this->subject->moveRecord('foo', 1, [], $correlationId);
        self::assertSame(1, $this->getRecordCountByCorrelationId($correlationId));
    }

    /**
     * @return array<int, int> uid => recuid
     */
    private function getRecordUidsOfHistoryEntries(): array
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable('sys_history')
            ->executeQuery('SELECT uid, recuid FROM sys_history ORDER BY uid')
            ->fetchAllAssociative();
        $recordUids = [];
        foreach ($rows as $row) {
            $recordUids[(int)$row['uid']] = (int)$row['recuid'];
        }
        return $recordUids;
    }

    #[Test]
    public function publishRecordKeepsUnrelatedEntriesWhenThereIsNoVersionedRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PublishMigrationEntries.csv');
        $subject = new RecordHistoryStore(RecordHistoryStore::USER_BACKEND, 1, null, 1700000600, 1);

        // A record created in a workspace keeps its uid, so it is published with a versioned uid of 0
        $subject->publishRecord('tt_content', 50, 0, []);

        // Entry 1 has recuid 0 and must not be bound to the published record
        self::assertSame(0, $this->getRecordUidsOfHistoryEntries()[1]);
    }

    #[Test]
    public function publishRecordOnlyMigratesEntriesOfThePublishedWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PublishMigrationEntries.csv');
        $subject = new RecordHistoryStore(RecordHistoryStore::USER_BACKEND, 1, null, 1700000600, 1);

        $subject->publishRecord('tt_content', 50, 99, []);

        $recordUids = $this->getRecordUidsOfHistoryEntries();
        // Entries 2 and 3 belong to the published version of the workspace being published
        self::assertSame(50, $recordUids[2]);
        self::assertSame(50, $recordUids[3]);
        // Entry 4 is a live entry and entry 5 belongs to another table
        self::assertSame(99, $recordUids[4]);
        self::assertSame(99, $recordUids[5]);
    }
}
