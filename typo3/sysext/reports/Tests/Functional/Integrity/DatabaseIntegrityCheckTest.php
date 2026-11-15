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

namespace TYPO3\CMS\Reports\Tests\Functional\Integrity;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\Domain\Repository\Fixtures\QueryCountingMiddleware;
use TYPO3\CMS\Reports\Integrity\DatabaseIntegrityCheck;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DatabaseIntegrityCheckTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['reports'];

    protected array $configurationToUseInTestInstance = [
        'DB' => [
            'globalDriverMiddlewares' => [
                'typo3-tests/query-counting' => [
                    'target' => QueryCountingMiddleware::class,
                ],
            ],
        ],
    ];

    #[Test]
    public function genTreeVisitsEachPageOnlyOnceWithCircularVersionReferences(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CircularPages.csv');
        $subject = $this->get(DatabaseIntegrityCheck::class);
        $subject->genTree(0);

        self::assertSame([1, 2, 3, 4], array_keys($subject->getPageIdArray()));
        self::assertSame([1 => 4], $subject->getRecStats()['doktype']);
    }

    #[Test]
    public function genTreeCollectsEachPageConnectedToTheRootOnce(): void
    {
        $subject = $this->createSubjectForPageTree();
        $pageIds = array_keys($subject->getPageIdArray());
        sort($pageIds);
        self::assertSame([1, 2, 3, 4, 6, 7, 8, 9], $pageIds);
        self::assertSame([5], array_keys($subject->getPageTranslatedPageIDArray()));
    }

    #[Test]
    public function genTreeCountsEachPageOnce(): void
    {
        $subject = $this->createSubjectForPageTree();
        $statistics = $subject->getRecStats();
        self::assertCount(9, $statistics['all_valid']['pages']);
        self::assertSame([3 => 3], $statistics['deleted']['pages']);
        self::assertSame(1, $statistics['hidden']);
        self::assertSame([1 => 8, 254 => 1], $statistics['doktype']);
    }

    #[Test]
    public function genTreeCountsEachPageOnceWhenCalledAgain(): void
    {
        $subject = $this->createSubjectForPageTree();
        $subject->genTree(0);
        $statistics = $subject->getRecStats();
        self::assertCount(9, $statistics['all_valid']['pages']);
        self::assertSame(1, $statistics['hidden']);
        self::assertSame([1 => 8, 254 => 1], $statistics['doktype']);
    }
    #[Test]
    public function genTreeUsesOneQuery(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTree.csv');
        QueryCountingMiddleware::$count = 0;
        $this->get(DatabaseIntegrityCheck::class)->genTree(0);
        self::assertSame(1, QueryCountingMiddleware::$count);
    }

    #[Test]
    public function recordsAreCountedAndLostRecordsAreFoundForManyPages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTree.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('pages');
        // More pages than any supported database accepts as placeholders in one statement
        foreach (array_chunk(range(100, 70099), 1000) as $pageIds) {
            $connection->bulkInsert('pages', array_map(static fn(int $pageId): array => [$pageId, 1, 'Page ' . $pageId], $pageIds), ['uid', 'pid', 'title']);
        }
        $subject = $this->get(DatabaseIntegrityCheck::class);
        $subject->genTree(0);
        $pageIds = [0, ...array_keys($subject->getPageIdArray())];

        $counts = $subject->countRecords($pageIds);
        $lostRecords = $subject->lostRecords($pageIds);

        // 70000 new pages and the 9 pages of the fixture on a page connected to the root, one of them deleted
        self::assertSame(70009, (int)$counts['all']['pages']);
        self::assertSame(70008, (int)$counts['non_deleted']['pages']);
        $lostPageIds = array_keys($lostRecords['pages']);
        sort($lostPageIds);
        self::assertSame([10, 11, 12], $lostPageIds);
    }

    #[Test]
    public function pagesNotConnectedToTheRootAreLost(): void
    {
        $subject = $this->createSubjectForPageTree();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $lostRecords = $subject->lostRecords([0, ...array_keys($subject->getPageIdArray()), ...array_keys($subject->getPageTranslatedPageIDArray())]);
        $lostPageIds = array_keys($lostRecords['pages'] ?? []);
        sort($lostPageIds);
        self::assertSame([10, 11, 12], $lostPageIds);
    }

    private function createSubjectForPageTree(): DatabaseIntegrityCheck
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTree.csv');
        $subject = $this->get(DatabaseIntegrityCheck::class);
        $subject->genTree(0);
        return $subject;
    }
}
