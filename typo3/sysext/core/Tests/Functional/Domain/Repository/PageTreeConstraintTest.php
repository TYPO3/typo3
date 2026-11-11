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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageDescendantsRepository;
use TYPO3\CMS\Core\Tests\Functional\Domain\Repository\Fixtures\QueryCountingMiddleware;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageTreeConstraintTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageDescendants.csv');
    }

    public static function constraintMatchesThePagesOfTheTreeDataProvider(): iterable
    {
        foreach ([0, 1, 2] as $workspaceId) {
            foreach ([1, 2, 99] as $depth) {
                yield 'workspace ' . $workspaceId . ', depth ' . $depth => [[1], $depth, $workspaceId, false];
                yield 'workspace ' . $workspaceId . ', depth ' . $depth . ', with live constraint' => [[1], $depth, $workspaceId, true];
            }
        }
        yield 'several pages' => [[2, 3], 99, 0, false];
        yield 'cycling page tree' => [[20], 99, 0, false];
    }

    #[Test]
    #[DataProvider('constraintMatchesThePagesOfTheTreeDataProvider')]
    public function constraintMatchesThePagesOfTheTree(array $pageIds, int $depth, int $workspaceId, bool $withLiveConstraint): void
    {
        $subject = $this->get(PageDescendantsRepository::class);
        $liveConstraint = $this->createLiveConstraint($withLiveConstraint);

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->andWhere($subject->createPageTreeConstraint($queryBuilder, 'pid', $pageIds, $depth, $workspaceId, $liveConstraint));
        $actual = $queryBuilder->executeQuery()->fetchFirstColumn();

        $expectedPageIds = [...$pageIds, ...array_column($subject->findDescendants($pageIds, $depth, $workspaceId, $liveConstraint), 'uid')];
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->andWhere($queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($expectedPageIds, Connection::PARAM_INT_ARRAY)));
        $expected = $queryBuilder->executeQuery()->fetchFirstColumn();

        self::assertNotSame([], $expected);
        self::assertSame($expected, $actual);
    }

    /**
     * A table mapped to another database connection than "pages" cannot join the page tree
     * in a subquery, so the constraint holds the uids of the pages, resolved the same way.
     */
    #[Test]
    #[DataProvider('constraintMatchesThePagesOfTheTreeDataProvider')]
    public function constraintOnAnotherConnectionMatchesThePagesOfTheTree(array $pageIds, int $depth, int $workspaceId, bool $withLiveConstraint): void
    {
        $subject = $this->get(PageDescendantsRepository::class);
        $liveConstraint = $this->createLiveConstraint($withLiveConstraint);

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->andWhere($subject->createPageTreeConstraint($queryBuilder, 'pid', $pageIds, $depth, $workspaceId, $liveConstraint));
        $expected = $queryBuilder->executeQuery()->fetchFirstColumn();

        // A connection instance of its own, as the connection pool returns for another connection name
        $otherConnection = clone $this->get(ConnectionPool::class)->getConnectionForTable('pages');
        $queryBuilder = $this->createQueryBuilder($otherConnection);
        $queryBuilder->andWhere($subject->createPageTreeConstraint($queryBuilder, 'pid', $pageIds, $depth, $workspaceId, $liveConstraint));

        self::assertStringNotContainsStringIgnoringCase('with recursive', $queryBuilder->getSQL());
        self::assertNotSame([], $expected);
        self::assertSame($expected, $queryBuilder->executeQuery()->fetchFirstColumn());
    }

    #[Test]
    public function constraintMatchesTheGivenPagesOnlyWithoutDepth(): void
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->andWhere($this->get(PageDescendantsRepository::class)->createPageTreeConstraint($queryBuilder, 'pid', [1], 0, 0));
        self::assertSame([2, 3, 10], array_map(intval(...), $queryBuilder->executeQuery()->fetchFirstColumn()));
    }

    #[Test]
    public function constraintMatchesNothingWithoutPages(): void
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->andWhere($this->get(PageDescendantsRepository::class)->createPageTreeConstraint($queryBuilder, 'pid', [], 99, 0));
        self::assertSame([], $queryBuilder->executeQuery()->fetchFirstColumn());
    }

    #[Test]
    public function constraintCanBeUsedTwiceInOneQuery(): void
    {
        $subject = $this->get(PageDescendantsRepository::class);
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->andWhere(
            $subject->createPageTreeConstraint($queryBuilder, 'pid', [2], 99, 0),
            $subject->createPageTreeConstraint($queryBuilder, 'uid', [1], 99, 0),
        );
        self::assertSame([4, 5], array_map(intval(...), $queryBuilder->executeQuery()->fetchFirstColumn()));
    }

    #[Test]
    public function constraintNeedsNoFurtherQuery(): void
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->andWhere($this->get(PageDescendantsRepository::class)->createPageTreeConstraint($queryBuilder, 'pid', [1], 99, 1));
        QueryCountingMiddleware::$count = 0;
        $queryBuilder->executeQuery()->fetchAllAssociative();
        self::assertSame(1, QueryCountingMiddleware::$count);
    }

    /**
     * Selects the uids of live pages in the default language, as records of any table would be selected.
     */
    private function createQueryBuilder(?Connection $connection = null): QueryBuilder
    {
        $queryBuilder = $connection?->createQueryBuilder() ?? $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('uid');
    }

    private function createLiveConstraint(bool $withLiveConstraint): ?\Closure
    {
        return $withLiveConstraint
            ? static fn(ExpressionBuilder $expr, string $alias): string => (string)$expr->neq($alias . '.perms_everybody', 0)
            : null;
    }
}
