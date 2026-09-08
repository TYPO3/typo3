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

namespace TYPO3\CMS\Scheduler\Tests\Functional\Task;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;
use TYPO3\CMS\Scheduler\Tests\Functional\Task\Fixtures\SmallBatchTableGarbageCollectionTask;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TableGarbageCollectionTaskTest extends FunctionalTestCase
{
    private const DAY = 86400;

    protected array $coreExtensionsToLoad = ['scheduler'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/scheduler/Tests/Functional/Task/Fixtures/Extensions/test_table_garbage_collection',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getConnectionPool()->getConnectionForTable('sys_log')->truncate('sys_log');
        $this->getConnectionPool()->getConnectionForTable('sys_http_report')->truncate('sys_http_report');
    }

    #[Test]
    public function deletesOnlyRowsOlderThanTheConfiguredExpirePeriod(): void
    {
        $now = time();
        $this->createLogRows([$now, $now - 179 * self::DAY, $now - 181 * self::DAY]);

        $task = new TableGarbageCollectionTask();
        $task->setTaskParameters(['selected_tables' => 'sys_log', 'number_of_days' => 0]);
        self::assertTrue($task->execute());

        self::assertSame([$now, $now - 179 * self::DAY], $this->getLogTimestamps());
    }

    #[Test]
    public function numberOfDaysOverridesTheConfiguredExpirePeriod(): void
    {
        $now = time();
        $this->createLogRows([$now, $now - 29 * self::DAY, $now - 31 * self::DAY, $now - 181 * self::DAY]);

        $task = new TableGarbageCollectionTask();
        $task->setTaskParameters(['selected_tables' => 'sys_log', 'number_of_days' => 30]);
        self::assertTrue($task->execute());

        self::assertSame([$now, $now - 29 * self::DAY], $this->getLogTimestamps());
    }

    #[Test]
    public function deletesExpiredRowsOfAllConfiguredTables(): void
    {
        $now = time();
        $GLOBALS['EXEC_TIME'] = $now;
        $this->createLogRows([$now, $now - 181 * self::DAY]);
        $this->createHttpReportRows([$now, $now - 31 * self::DAY]);
        $this->createExpireRows([0, $now + self::DAY, $now - self::DAY]);

        $task = new TableGarbageCollectionTask();
        $task->setTaskParameters(['all_tables' => true]);
        self::assertTrue($task->execute());

        self::assertSame([$now], $this->getLogTimestamps());
        self::assertSame([$now], $this->getHttpReportTimestamps());
        self::assertSame([0, $now + self::DAY], $this->getExpireValues());
    }

    #[Test]
    public function deletesOnlyRowsWithAnExpiredExpireField(): void
    {
        $now = time();
        $GLOBALS['EXEC_TIME'] = $now;
        $this->createExpireRows([0, $now + self::DAY, $now - self::DAY, $now]);

        $task = new TableGarbageCollectionTask();
        $task->setTaskParameters(['selected_tables' => 'tx_testtablegarbagecollection_expire']);
        self::assertTrue($task->execute());

        self::assertSame([0, $now + self::DAY], $this->getExpireValues());
    }

    #[Test]
    public function deletesAllExpiredRowsInBatchesAndKeepsTheOtherRows(): void
    {
        $now = time();
        $expired = $now - 181 * self::DAY;
        // Expired and kept rows alternate, so a batch limited by the primary key
        // must not remove a row that is not expired but sorts within the batch.
        $this->createLogRows([
            $expired, $now, $expired, $now, $expired, $now,
            $expired, $now, $expired, $now, $expired, $now,
        ]);

        $task = new SmallBatchTableGarbageCollectionTask();
        $task->setTaskParameters(['selected_tables' => 'sys_log', 'number_of_days' => 0]);
        self::assertTrue($task->execute());

        self::assertSame([$now, $now, $now, $now, $now, $now], $this->getLogTimestamps());
    }

    #[Test]
    public function deletesAllExpiredRowsInBatchesOfATableWithoutIntegerPrimaryKey(): void
    {
        $now = time();
        $expired = $now - 31 * self::DAY;
        $this->createHttpReportRows([
            $expired, $now, $expired, $now, $expired, $now,
            $expired, $now, $expired, $now, $expired, $now,
        ]);

        $task = new SmallBatchTableGarbageCollectionTask();
        $task->setTaskParameters(['selected_tables' => 'sys_http_report', 'number_of_days' => 0]);
        self::assertTrue($task->execute());

        self::assertSame([$now, $now, $now, $now, $now, $now], $this->getHttpReportTimestamps());
    }

    #[Test]
    public function deletesAllExpiredRowsInBatchesWithoutRowsToKeep(): void
    {
        $now = time();
        $expired = $now - 181 * self::DAY;
        $this->createLogRows([$expired, $expired, $expired, $expired, $expired, $expired]);

        $task = new SmallBatchTableGarbageCollectionTask();
        $task->setTaskParameters(['selected_tables' => 'sys_log', 'number_of_days' => 0]);
        self::assertTrue($task->execute());

        self::assertSame([], $this->getLogTimestamps());
    }

    #[Test]
    public function deletesExpiredRowsOfATableWithACompositePrimaryKey(): void
    {
        $now = time();
        $expired = $now - 31 * self::DAY;
        $this->createCompositeRows([$expired, $now, $expired, $now, $expired, $now, $expired, $now]);

        $task = new SmallBatchTableGarbageCollectionTask();
        $task->setTaskParameters(['selected_tables' => 'tx_testtablegarbagecollection_composite']);
        self::assertTrue($task->execute());

        self::assertSame([$now, $now, $now, $now], $this->getCompositeTimestamps());
    }

    #[Test]
    public function batchesAreLimitedByTheSingleColumnPrimaryKeyOfATable(): void
    {
        $task = new TableGarbageCollectionTask();
        $determineBatchKey = new \ReflectionMethod($task, 'determineBatchKey');
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_log');

        self::assertSame(['uid', Connection::PARAM_INT], $determineBatchKey->invoke($task, $connection, 'sys_log'));
        self::assertSame(['uuid', Connection::PARAM_STR], $determineBatchKey->invoke($task, $connection, 'sys_http_report'));
        self::assertNull($determineBatchKey->invoke($task, $connection, 'tx_testtablegarbagecollection_composite'));
    }

    #[Test]
    public function throwsExceptionIfSelectedTableIsNotConfigured(): void
    {
        $task = new TableGarbageCollectionTask();
        $task->setTaskParameters(['selected_tables' => 'tx_not_configured_table']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1308354399);
        $task->execute();
    }

    /**
     * @param int[] $timestamps
     */
    private function createLogRows(array $timestamps): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_log');
        foreach ($timestamps as $timestamp) {
            $connection->insert('sys_log', ['tstamp' => $timestamp], ['tstamp' => Connection::PARAM_INT]);
        }
    }

    /**
     * @param int[] $timestamps
     */
    private function createHttpReportRows(array $timestamps): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_http_report');
        $number = 0;
        foreach ($timestamps as $timestamp) {
            $number++;
            $connection->insert(
                'sys_http_report',
                [
                    'uuid' => sprintf('11111111-1111-1111-1111-%012d', $number),
                    'created' => $timestamp,
                    'changed' => $timestamp,
                    'type' => 'csp-report',
                    'scope' => 'frontend',
                    'request_time' => $timestamp,
                    'summary' => sprintf('summary-%d', $number),
                ],
                [
                    'created' => Connection::PARAM_INT,
                    'changed' => Connection::PARAM_INT,
                    'request_time' => Connection::PARAM_INT,
                ]
            );
        }
    }

    /**
     * @param int[] $expireValues
     */
    private function createExpireRows(array $expireValues): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_testtablegarbagecollection_expire');
        foreach ($expireValues as $expireValue) {
            $connection->insert(
                'tx_testtablegarbagecollection_expire',
                ['expire_field' => $expireValue],
                ['expire_field' => Connection::PARAM_INT]
            );
        }
    }

    /**
     * @param int[] $timestamps
     */
    private function createCompositeRows(array $timestamps): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_testtablegarbagecollection_composite');
        $number = 0;
        foreach ($timestamps as $timestamp) {
            $number++;
            $connection->insert(
                'tx_testtablegarbagecollection_composite',
                [
                    'identifier' => sprintf('identifier-%02d', $number),
                    'scope' => 'test',
                    'tstamp' => $timestamp,
                ],
                ['tstamp' => Connection::PARAM_INT]
            );
        }
    }

    /**
     * @return int[]
     */
    private function getLogTimestamps(): array
    {
        return $this->getIntegerColumn('sys_log', 'tstamp', 'uid');
    }

    /**
     * @return int[]
     */
    private function getHttpReportTimestamps(): array
    {
        return $this->getIntegerColumn('sys_http_report', 'changed', 'uuid');
    }

    /**
     * @return int[]
     */
    private function getExpireValues(): array
    {
        return $this->getIntegerColumn('tx_testtablegarbagecollection_expire', 'expire_field', 'uid');
    }

    /**
     * @return int[]
     */
    private function getCompositeTimestamps(): array
    {
        return $this->getIntegerColumn('tx_testtablegarbagecollection_composite', 'tstamp', 'identifier');
    }

    /**
     * @return int[]
     */
    private function getIntegerColumn(string $table, string $field, string $orderBy): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $values = $queryBuilder
            ->select($field)
            ->from($table)
            ->orderBy($orderBy)
            ->executeQuery()
            ->fetchFirstColumn();
        return array_map(intval(...), $values);
    }
}
