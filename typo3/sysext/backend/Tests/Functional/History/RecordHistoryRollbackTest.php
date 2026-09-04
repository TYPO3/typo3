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
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\History\RecordHistoryRollback;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordHistoryRollbackTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    /**
     * Modifies and then deletes pages:4 and returns the uid of the
     * history entry the rollback should start from.
     */
    private function modifyAndDeletePage(): int
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['pages' => [4 => ['title' => 'Changed title']]], []);
        $dataHandler->process_datamap();
        $firstEntryOfRollback = (int)$this->getConnectionPool()
            ->getConnectionForTable('sys_history')
            ->executeQuery('SELECT MAX(uid) FROM sys_history')
            ->fetchOne();

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['pages' => [4 => ['delete' => 1]]]);
        $dataHandler->process_cmdmap();

        return $firstEntryOfRollback;
    }

    private function getDiffForRollback(int $firstEntryOfRollback): array
    {
        $recordHistory = new RecordHistory('pages:4');
        $recordHistory->setLastHistoryEntryNumber($firstEntryOfRollback);
        return $recordHistory->getDiff($recordHistory->getChangeLog());
    }

    private function getPage(): array
    {
        $page = $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->executeQuery('SELECT title, deleted FROM pages WHERE uid = 4')
            ->fetchAssociative();
        return ['title' => $page['title'], 'deleted' => (int)$page['deleted']];
    }

    /**
     * @return string[] correlation id scopes of all history entries written after $afterUid
     */
    private function getCorrelationScopesWrittenAfter(int $afterUid): array
    {
        $correlationIds = $this->getConnectionPool()
            ->getConnectionForTable('sys_history')
            ->executeQuery('SELECT correlation_id FROM sys_history WHERE uid > ?', [$afterUid])
            ->fetchFirstColumn();
        return array_map(
            static fn(string $correlationId): string => (string)CorrelationId::fromString($correlationId)->getScope(),
            $correlationIds
        );
    }

    private function getLatestHistoryEntry(): int
    {
        return (int)$this->getConnectionPool()
            ->getConnectionForTable('sys_history')
            ->executeQuery('SELECT MAX(uid) FROM sys_history')
            ->fetchOne();
    }

    private function movePage(int $uid, int $destination): int
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['pages' => [$uid => ['move' => $destination]]]);
        $dataHandler->process_cmdmap();

        return $this->getLatestHistoryEntry();
    }

    private function getDiffForRollbackOf(string $element, int $firstEntryOfRollback): array
    {
        $recordHistory = new RecordHistory($element);
        $recordHistory->setLastHistoryEntryNumber($firstEntryOfRollback);
        return $recordHistory->getDiff($recordHistory->getChangeLog());
    }

    /**
     * @return array{pid: int, sorting: int}
     */
    private function getPosition(int $uid): array
    {
        $row = $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->executeQuery('SELECT pid, sorting FROM pages WHERE uid = ?', [$uid])
            ->fetchAssociative();
        return ['pid' => (int)$row['pid'], 'sorting' => (int)$row['sorting']];
    }

    #[Test]
    public function rollbackOfASingleRecordRestoresAndUndeletesIt(): void
    {
        $firstEntryOfRollback = $this->modifyAndDeletePage();
        self::assertSame(['title' => 'Changed title', 'deleted' => 1], $this->getPage());

        $this->get(RecordHistoryRollback::class)->performRollback(
            'pages:4',
            $this->getDiffForRollback($firstEntryOfRollback)
        );

        self::assertSame(['title' => 'Dummy 1-2-3-4', 'deleted' => 0], $this->getPage());
    }

    #[Test]
    public function rollbackWritesAllHistoryEntriesWithTheSameCorrelationScope(): void
    {
        $firstEntryOfRollback = $this->modifyAndDeletePage();
        $lastEntryBeforeRollback = (int)$this->getConnectionPool()
            ->getConnectionForTable('sys_history')
            ->executeQuery('SELECT MAX(uid) FROM sys_history')
            ->fetchOne();

        $this->get(RecordHistoryRollback::class)->performRollback(
            'pages:4',
            $this->getDiffForRollback($firstEntryOfRollback)
        );

        $scopes = $this->getCorrelationScopesWrittenAfter($lastEntryBeforeRollback);
        // The undelete is written by the command map run, the field restore by the data map run
        self::assertGreaterThanOrEqual(2, count($scopes));
        self::assertCount(1, array_unique($scopes));
    }

    #[Test]
    public function rollbackMovesARecordBackToItsPreviousPage(): void
    {
        $firstEntryOfRollback = $this->movePage(4, 1);
        self::assertSame(1, $this->getPosition(4)['pid']);

        $this->get(RecordHistoryRollback::class)->performRollback(
            'ALL',
            $this->getDiffForRollbackOf('pages:4', $firstEntryOfRollback)
        );

        // The sorting value itself is handed out by DataHandler and is not restored literally,
        // page 4 is the only child of page 3 so its position is defined by the page alone
        self::assertSame(3, $this->getPosition(4)['pid']);
    }

    #[Test]
    public function rollbackRestoresThePositionAmongTheSiblingsOfThePreviousPage(): void
    {
        // pages 2 and 5 are both children of page 1, page 5 comes second
        $firstEntryOfRollback = $this->movePage(5, 3);
        self::assertSame(3, $this->getPosition(5)['pid']);

        $this->get(RecordHistoryRollback::class)->performRollback(
            'ALL',
            $this->getDiffForRollbackOf('pages:5', $firstEntryOfRollback)
        );

        self::assertSame(1, $this->getPosition(5)['pid']);
        self::assertGreaterThan($this->getPosition(2)['sorting'], $this->getPosition(5)['sorting']);
    }

    #[Test]
    public function rollbackRestoresThePositionWithinTheSamePage(): void
    {
        // pages 2 and 5 are both children of page 1, page 5 comes second
        self::assertGreaterThan($this->getPosition(2)['sorting'], $this->getPosition(5)['sorting']);

        // Moving page 5 to the top of page 1 puts it in front of page 2
        $firstEntryOfRollback = $this->movePage(5, 1);
        self::assertLessThan($this->getPosition(2)['sorting'], $this->getPosition(5)['sorting']);

        $this->get(RecordHistoryRollback::class)->performRollback(
            'ALL',
            $this->getDiffForRollbackOf('pages:5', $firstEntryOfRollback)
        );

        self::assertSame(1, $this->getPosition(5)['pid']);
        self::assertGreaterThan($this->getPosition(2)['sorting'], $this->getPosition(5)['sorting']);
    }
}
