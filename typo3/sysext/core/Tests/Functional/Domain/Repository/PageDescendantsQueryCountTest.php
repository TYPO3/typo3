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

namespace TYPO3\CMS\Core\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageDescendantsRepository;
use TYPO3\CMS\Core\Tests\Functional\Domain\Repository\Fixtures\QueryCountingMiddleware;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The number of queries to find the descendants of a page must not depend on
 * the size of the page tree below it.
 */
final class PageDescendantsQueryCountTest extends FunctionalTestCase
{
    private const SMALL_TREE_ROOT = 1;
    private const LARGE_TREE_ROOT = 2;

    protected array $configurationToUseInTestInstance = [
        'DB' => [
            'globalDriverMiddlewares' => [
                'typo3-tests/query-counting' => [
                    'target' => QueryCountingMiddleware::class,
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $connectionPool = $this->get(ConnectionPool::class);
        $rows = [
            ...$this->createTree(self::SMALL_TREE_ROOT, 2, 2, 100),
            ...$this->createTree(self::LARGE_TREE_ROOT, 3, 5, 1000),
        ];
        $connection = $connectionPool->getConnectionForTable('pages');
        foreach (array_chunk($rows, 200) as $chunk) {
            $connection->bulkInsert('pages', $chunk, array_keys($chunk[0]));
        }
        $connectionPool->getConnectionForTable('be_users')->insert('be_users', [
            'uid' => 2, 'pid' => 0, 'username' => 'editor', 'admin' => 0, 'db_mountpoints' => '1,2', 'workspace_perms' => 1,
        ]);
    }

    public static function workspaceDataProvider(): iterable
    {
        yield 'live' => [0];
        yield 'workspace' => [1];
    }

    #[Test]
    #[DataProvider('workspaceDataProvider')]
    public function pageDescendantsRepositoryUsesOneQuery(int $workspaceId): void
    {
        $subject = $this->get(PageDescendantsRepository::class);
        [$small, $large] = $this->countQueriesForBothTrees(static fn(int $root): array => $subject->findDescendants([$root], 99, $workspaceId, fields: ['*']));
        self::assertSame(1, $small);
        self::assertSame(1, $large);
    }

    #[Test]
    #[DataProvider('workspaceDataProvider')]
    public function flattenedPagesOfPageTreeRepositoryDoNotDependOnTreeSize(int $workspaceId): void
    {
        $permsClause = $this->setUpBackendUser(2)->getPagePermsClause(Permission::PAGE_SHOW);
        [$small, $large] = $this->countQueriesForBothTrees(static function (int $root) use ($workspaceId, $permsClause): array {
            $repository = GeneralUtility::makeInstance(PageTreeRepository::class, $workspaceId);
            $repository->setAdditionalWhereClause($permsClause);
            return $repository->getFlattenedPages([$root], 99);
        });
        self::assertSame($small, $large);
    }

    /**
     * @return array{int, int} Number of queries for the small and the large tree
     */
    private function countQueriesForBothTrees(\Closure $call): array
    {
        // Warm up runtime caches, which are not part of the measurement
        $call(self::SMALL_TREE_ROOT);
        $counts = [];
        foreach ([self::SMALL_TREE_ROOT, self::LARGE_TREE_ROOT] as $root) {
            QueryCountingMiddleware::$count = 0;
            $call($root);
            $counts[] = QueryCountingMiddleware::$count;
        }
        return $counts;
    }

    /**
     * Creates a page tree below a root page with the given number of subpages per page and
     * levels. In workspace 1, every third page has a version, every fifth is moved to the root page.
     *
     * @return list<array<string, int|string>>
     */
    private function createTree(int $rootPageId, int $subpagesPerPage, int $levels, int $firstUid): array
    {
        $rows = [$this->createPage($rootPageId, 0, 256)];
        $parentPageIds = [$rootPageId];
        $uid = $firstUid;
        for ($level = 1; $level <= $levels; $level++) {
            $nextParentPageIds = [];
            foreach ($parentPageIds as $parentPageId) {
                for ($i = 1; $i <= $subpagesPerPage; $i++) {
                    $rows[] = $this->createPage(++$uid, $parentPageId, $i * 256);
                    $nextParentPageIds[] = $uid;
                }
            }
            $parentPageIds = $nextParentPageIds;
        }
        $versionUid = $firstUid + 500;
        foreach ($rows as $row) {
            if ($row['uid'] % 5 === 0) {
                $rows[] = $this->createPage(++$versionUid, $rootPageId, 64, $row['uid'], 4);
            } elseif ($row['uid'] % 3 === 0) {
                $rows[] = $this->createPage(++$versionUid, $row['pid'], $row['sorting'], $row['uid'], 0);
            }
        }
        return $rows;
    }

    private function createPage(int $uid, int $pid, int $sorting, int $liveUid = 0, int $versionState = 0): array
    {
        return [
            'uid' => $uid,
            'pid' => $pid,
            'sorting' => $sorting,
            'title' => 'Page ' . $uid,
            't3ver_oid' => $liveUid,
            't3ver_wsid' => $liveUid > 0 ? 1 : 0,
            't3ver_state' => $versionState,
            'perms_userid' => 2,
            'perms_user' => 31,
            'perms_everybody' => 1,
        ];
    }
}
