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

namespace TYPO3\CMS\Core\Tests\Functional\Pagination;

use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Pagination\QueryBuilderPaginator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class QueryBuilderPaginatorTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_tca',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/pagination.csv');
    }

    #[Test]
    #[DataProvider('paginationReturnsCorrectItemsDataProvider')]
    public function paginationReturnsCorrectItems(int $currentPage, int $itemsPerPage, array $expectedUids): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_pagination_item');
        $queryBuilder = $queryBuilder
            ->select('*')
            ->from('tx_pagination_item')
            ->where($queryBuilder->expr()->gt('uid', $queryBuilder->createNamedParameter(3, ParameterType::INTEGER)))
            ->orderBy('uid', 'asc');

        $paginator = new QueryBuilderPaginator($queryBuilder, $currentPage, $itemsPerPage);

        $paginatedItems = $paginator->getPaginatedItems();
        self::assertSame($expectedUids, array_column((array)$paginatedItems, 'uid'));
    }

    public static function paginationReturnsCorrectItemsDataProvider(): array
    {
        return [
            'page-1' => [
                1, 3, [4, 5, 6],
            ],
            'page-3' => [
                6, 2, [14],
            ],
        ];
    }

    /**
     * A short integration test to check that the fixtures are as expected
     */
    #[Test]
    public function integration(): void
    {
        $queryResult = $this->getQueryBuilder()->executeQuery()->fetchAllAssociative();
        self::assertCount(14, $queryResult);
    }

    #[Test]
    public function checkPaginatorWithDefaultConfiguration(): void
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $paginator = new QueryBuilderPaginator($queryBuilder);

        self::assertSame(2, $paginator->getNumberOfPages());
        self::assertSame(0, $paginator->getKeyOfFirstPaginatedItem());
        self::assertSame(9, $paginator->getKeyOfLastPaginatedItem());
        self::assertCount(10, $paginator->getPaginatedItems());
    }

    #[Test]
    public function paginatorRespectsItemsPerPageConfiguration(): void
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $paginator = new QueryBuilderPaginator(
            $queryBuilder,
            1,
            3
        );

        self::assertSame(5, $paginator->getNumberOfPages());
        self::assertSame(0, $paginator->getKeyOfFirstPaginatedItem());
        self::assertSame(2, $paginator->getKeyOfLastPaginatedItem());
        self::assertCount(3, $paginator->getPaginatedItems());
    }

    #[Test]
    public function paginatorRespectsItemsPerPageConfigurationAndCurrentPage(): void
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $paginator = new QueryBuilderPaginator(
            $queryBuilder,
            3,
            3
        );

        self::assertSame(5, $paginator->getNumberOfPages());
        self::assertSame(6, $paginator->getKeyOfFirstPaginatedItem());
        self::assertSame(8, $paginator->getKeyOfLastPaginatedItem());
        self::assertCount(3, $paginator->getPaginatedItems());
    }

    #[Test]
    public function paginatorProperlyCalculatesLastPage(): void
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $paginator = new QueryBuilderPaginator(
            $queryBuilder,
            5,
            3
        );

        self::assertSame(5, $paginator->getNumberOfPages());
        self::assertSame(12, $paginator->getKeyOfFirstPaginatedItem());
        self::assertSame(13, $paginator->getKeyOfLastPaginatedItem());
        self::assertCount(2, $paginator->getPaginatedItems());
    }

    #[Test]
    public function withCurrentPageNumberThrowsInvalidArgumentExceptionIfCurrentPageIsLowerThanOne(): void
    {
        $this->expectExceptionCode(1573047338);

        $paginator = new QueryBuilderPaginator(
            $this->getQueryBuilder(),
            1,
            3
        );
        $paginator->withCurrentPageNumber(0);
    }

    #[Test]
    public function paginatorSetsCurrentPageToLastPageIfCurrentPageExceedsMaximum(): void
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $paginator = new QueryBuilderPaginator(
            $queryBuilder,
            3,
            10
        );

        self::assertEquals(2, $paginator->getCurrentPageNumber());
        self::assertEquals(2, $paginator->getNumberOfPages());
        self::assertCount(4, $paginator->getPaginatedItems());
    }

    #[Test]
    public function paginatorProperlyCalculatesOnlyOnePage(): void
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $paginator = new QueryBuilderPaginator(
            $queryBuilder,
            1,
            50
        );
        self::assertSame(1, $paginator->getNumberOfPages());
        self::assertSame(0, $paginator->getKeyOfFirstPaginatedItem());
        self::assertSame(13, $paginator->getKeyOfLastPaginatedItem());
        self::assertCount(14, $paginator->getPaginatedItems());
    }

    private function getQueryBuilder()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_pagination_item');
        $queryBuilder = $queryBuilder
            ->select('*')
            ->from('tx_pagination_item')
            ->orderBy('uid', 'asc');
        return $queryBuilder;
    }

}
