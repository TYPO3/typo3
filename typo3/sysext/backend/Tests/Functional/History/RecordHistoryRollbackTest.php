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
}
