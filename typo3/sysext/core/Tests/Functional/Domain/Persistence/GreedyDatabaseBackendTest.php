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

namespace TYPO3\CMS\Core\Tests\Functional\Domain\Persistence;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Persistence\GreedyDatabaseBackend;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The fixture page tree used here spreads the records over several pids:
 * uids 2, 3 and 4 are on pid 1, uids 5, 6 and 7 are on pid 2, uids 8 and 9 are on pid 3.
 */
final class GreedyDatabaseBackendTest extends FunctionalTestCase
{
    private GreedyDatabaseBackend $subject;
    private Context $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->get('cache.runtime')->flush();
        $this->subject = $this->get(GreedyDatabaseBackend::class);
        $this->context = new Context();
    }

    /**
     * Any row returned after this proves it has been resolved from the runtime
     * cache instead of being queried from the database again.
     */
    private function dropAllPages(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('pages')->truncate('pages');
    }

    /**
     * @return list<int>
     */
    private function getUids(array $uids): array
    {
        $rows = $this->subject->getRows('pages', $uids, $this->context);
        $resolvedUids = array_map(static fn(array $row): int => (int)$row['uid'], $rows);
        sort($resolvedUids);
        return $resolvedUids;
    }

    #[Test]
    public function rowsOfDifferentParentPagesAreResolvedWithAnEmptyCache(): void
    {
        self::assertSame([2, 3, 5], $this->getUids([2, 3, 5]));
    }

    #[Test]
    public function rowsOfDifferentParentPagesAreResolvedWhenOneUidIsAlreadyCached(): void
    {
        // Warms the cache with all rows of pid 1 (uids 2, 3 and 4).
        self::assertSame([2], $this->getUids([2]));

        // Uid 5 is on pid 2, which is not covered by the cached result set.
        self::assertSame([2, 5], $this->getUids([2, 5]));
    }

    #[Test]
    public function rowsOfDifferentParentPagesAreResolvedWhenAllUidsAreAlreadyCached(): void
    {
        self::assertSame([2], $this->getUids([2]));
        self::assertSame([5], $this->getUids([5]));
        self::assertSame([8], $this->getUids([8]));

        self::assertSame([2, 5, 8], $this->getUids([2, 5, 8]));
    }

    #[Test]
    public function rowsOfTheSameParentPageAreResolvedWhenOneUidIsAlreadyCached(): void
    {
        self::assertSame([2], $this->getUids([2]));
        self::assertSame([2, 3, 4], $this->getUids([2, 3, 4]));
    }

    #[Test]
    public function overlappingRelationsToDifferentParentPagesAreResolved(): void
    {
        // Two records relating to pages of different pids, sharing the relation to uid 5.
        self::assertSame([2, 5], $this->getUids([2, 5]));
        self::assertSame([5, 8], $this->getUids([5, 8]));
        self::assertSame([2, 5], $this->getUids([2, 5]));
    }

    #[Test]
    public function nonExistingUidsAreOmittedFromTheResult(): void
    {
        self::assertSame([2], $this->getUids([2, 99999]));
        // Repeated with a warm cache, and combined with a uid of another pid.
        self::assertSame([2], $this->getUids([2, 99999]));
        self::assertSame([2, 5], $this->getUids([2, 5, 99999]));
    }

    #[Test]
    public function cachedRowsOfTheSameParentPageAreReusedForOtherUids(): void
    {
        // Warms the cache with all rows of pid 1 (uids 2, 3 and 4).
        self::assertSame([2], $this->getUids([2]));
        $this->dropAllPages();

        self::assertSame([3, 4], $this->getUids([3, 4]));
    }

    #[Test]
    public function cachedRowsOfDifferentParentPagesAreReusedForOtherUids(): void
    {
        // Warms the cache with all rows of pid 1 (uids 2, 3, 4) and of pid 2 (uids 5, 6, 7).
        self::assertSame([2], $this->getUids([2]));
        self::assertSame([5], $this->getUids([5]));
        $this->dropAllPages();

        self::assertSame([4, 7], $this->getUids([4, 7]));
    }

    #[Test]
    public function cachedRowsAreKeptWhenAnAdditionalUidIsResolved(): void
    {
        // Warms the cache with all rows of pid 1 (uids 2, 3 and 4).
        self::assertSame([2], $this->getUids([2]));
        $this->dropAllPages();

        // Uid 5 is unknown and therefore looked up in the database - which no longer
        // has it. The already cached rows of pid 1 must not be discarded by that.
        self::assertSame([2], $this->getUids([2, 5]));
    }

    #[Test]
    public function cachedResultOfANonExistingUidIsReused(): void
    {
        self::assertSame([2], $this->getUids([2, 99999]));
        $this->dropAllPages();

        self::assertSame([3], $this->getUids([99999, 3]));
    }
}
