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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordIndexingTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'indexed_search',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordIndexing/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordIndexing/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordIndexing/index_config.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function newRecordIsIndexedWhenIndexingConfigurationRequestsIt(): void
    {
        $this->saveContentElement(2, 'NEW1', 'Indexable headline', 'Zeppelin');

        $rows = $this->getIndexedRows();
        self::assertCount(1, $rows);
        self::assertSame('Indexable headline', $rows[0]['item_title']);
        self::assertSame(2, (int)$rows[0]['data_page_id']);
        self::assertSame(1, (int)$rows[0]['freeIndexUid']);
        self::assertGreaterThan(0, (int)$rows[0]['recordUid']);
        self::assertStringContainsString('Zeppelin', $this->getFulltext($rows[0]['phash']));
    }

    #[Test]
    public function updatedRecordIsIndexedAgain(): void
    {
        $this->saveContentElement(2, 'NEW1', 'First headline', 'Zeppelin');
        $recordUid = (int)$this->getIndexedRows()[0]['recordUid'];

        $this->saveContentElement(2, $recordUid, 'Second headline', 'Zeppelin');

        $rows = $this->getIndexedRows();
        self::assertCount(1, $rows);
        self::assertSame('Second headline', $rows[0]['item_title']);
    }

    #[Test]
    public function recordIsNotIndexedWhenIndexOnChangeIsDisabled(): void
    {
        $this->saveContentElement(3, 'NEW1', 'Ignored headline', 'Zeppelin');

        self::assertSame([], $this->getIndexedRows());
    }

    #[Test]
    public function recordOnPageWithoutIndexingConfigurationIsNotIndexed(): void
    {
        $this->saveContentElement(1, 'NEW1', 'Ignored headline', 'Zeppelin');

        self::assertSame([], $this->getIndexedRows());
    }

    #[Test]
    public function deletedRecordIsRemovedFromIndex(): void
    {
        $this->saveContentElement(2, 'NEW1', 'Indexable headline', 'Zeppelin');
        $recordUid = (int)$this->getIndexedRows()[0]['recordUid'];

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['tt_content' => [$recordUid => ['delete' => 1]]]);
        $dataHandler->process_cmdmap();

        self::assertSame([], $this->getIndexedRows());
    }

    #[Test]
    public function hiddenRecordIsRemovedFromIndex(): void
    {
        $this->saveContentElement(2, 'NEW1', 'Indexable headline', 'Zeppelin');
        $recordUid = (int)$this->getIndexedRows()[0]['recordUid'];

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['tt_content' => [$recordUid => ['hidden' => 1]]], []);
        $dataHandler->process_datamap();

        self::assertSame([], $this->getIndexedRows());
    }

    #[Test]
    public function deletingPageRemovesItsIndexedRecords(): void
    {
        $this->saveContentElement(2, 'NEW1', 'Indexable headline', 'Zeppelin');
        self::assertCount(1, $this->getIndexedRows());

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['pages' => [2 => ['delete' => 1]]]);
        $dataHandler->process_cmdmap();

        self::assertSame([], $this->getIndexedRows());
    }

    public static function restrictedRecordDataProvider(): \Generator
    {
        yield 'restricted to a frontend user group' => [['fe_group' => '1']];
        yield 'shown at any login' => [['fe_group' => '-2']];
        yield 'scheduled for the future' => [['starttime' => time() + 86400]];
        yield 'expired' => [['starttime' => time() - 172800, 'endtime' => time() - 86400]];
    }

    #[DataProvider('restrictedRecordDataProvider')]
    #[Test]
    public function recordNotVisibleToAnonymousVisitorsIsNotIndexed(array $restriction): void
    {
        $this->saveContentElement(2, 'NEW1', 'Restricted headline', 'Zeppelin', $restriction);

        self::assertSame([], $this->getIndexedRows());
    }

    #[DataProvider('restrictedRecordDataProvider')]
    #[Test]
    public function recordNotVisibleToAnonymousVisitorsIsRemovedFromIndex(array $restriction): void
    {
        $this->saveContentElement(2, 'NEW1', 'Indexable headline', 'Zeppelin');
        $recordUid = (int)$this->getIndexedRows()[0]['recordUid'];

        $this->saveContentElement(2, $recordUid, 'Restricted headline', 'Zeppelin', $restriction);

        self::assertSame([], $this->getIndexedRows());
    }

    #[Test]
    public function recordHiddenAtLoginIsIndexed(): void
    {
        $this->saveContentElement(2, 'NEW1', 'Anonymous headline', 'Zeppelin', ['fe_group' => '-1']);

        self::assertCount(1, $this->getIndexedRows());
    }

    #[Test]
    public function passwordFieldIsNotIndexed(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['fe_users' => ['NEW1' => ['pid' => 4, 'username' => 'kasper', 'password' => 'Secret-Password-123', 'email' => 'kasper@example.com']]], []);
        $dataHandler->process_datamap();
        self::assertSame([], $dataHandler->errorLog);
        $passwordHash = (string)$this->get(ConnectionPool::class)->getConnectionForTable('fe_users')
            ->select(['password'], 'fe_users', ['username' => 'kasper'])
            ->fetchOne();

        $rows = $this->getIndexedRows();
        self::assertCount(1, $rows);
        self::assertSame('kasper', $rows[0]['item_title']);
        $fulltext = $this->getFulltext($rows[0]['phash']);
        self::assertStringContainsString('kasper@example.com', $fulltext);
        self::assertNotSame('', $passwordHash);
        self::assertStringNotContainsString($passwordHash, $fulltext);
        self::assertStringNotContainsString('Secret-Password-123', $fulltext);
    }

    private function saveContentElement(int $pid, int|string $id, string $header, string $bodytext, array $additionalFields = []): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['tt_content' => [$id => array_merge(['pid' => $pid, 'CType' => 'text', 'header' => $header, 'bodytext' => $bodytext], $additionalFields)]], []);
        $dataHandler->process_datamap();
        self::assertSame([], $dataHandler->errorLog);
    }

    private function getIndexedRows(): array
    {
        return $this->get(ConnectionPool::class)->getConnectionForTable('index_phash')
            ->select(['*'], 'index_phash')
            ->fetchAllAssociative();
    }

    private function getFulltext(string $phash): string
    {
        return (string)$this->get(ConnectionPool::class)->getConnectionForTable('index_fulltext')
            ->select(['fulltextdata'], 'index_fulltext', ['phash' => $phash])
            ->fetchOne();
    }
}
