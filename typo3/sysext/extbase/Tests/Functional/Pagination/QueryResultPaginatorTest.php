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

namespace TYPO3\CMS\Extbase\Tests\Functional\Pagination;

use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class QueryResultPaginatorTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var PostRepository
     */
    protected $postRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/posts.csv');
        $this->postRepository = $this->get(PostRepository::class);
    }

    /**
     * A short integration test to check that the fixtures are as expected
     *
     * @test
     */
    public function integration(): void
    {
        $queryResult = $this->postRepository->findAll();
        self::assertCount(14, $queryResult);
    }

    /**
     * @test
     */
    public function checkPaginatorWithDefaultConfiguration(): void
    {
        $paginator = new QueryResultPaginator($this->postRepository->findAll());

        self::assertSame(2, $paginator->getNumberOfPages());
        self::assertSame(0, $paginator->getKeyOfFirstPaginatedItem());
        self::assertSame(9, $paginator->getKeyOfLastPaginatedItem());
        self::assertCount(10, $paginator->getPaginatedItems());
    }

    /**
     * @test
     */
    public function paginatorRespectsItemsPerPageConfiguration(): void
    {
        $paginator = new QueryResultPaginator(
            $this->postRepository->findAll(),
            1,
            3
        );

        self::assertSame(5, $paginator->getNumberOfPages());
        self::assertSame(0, $paginator->getKeyOfFirstPaginatedItem());
        self::assertSame(2, $paginator->getKeyOfLastPaginatedItem());
        self::assertCount(3, $paginator->getPaginatedItems());
    }

    /**
     * @test
     */
    public function paginatorRespectsItemsPerPageConfigurationAndCurrentPage(): void
    {
        $paginator = new QueryResultPaginator(
            $this->postRepository->findAll(),
            3,
            3
        );

        self::assertSame(5, $paginator->getNumberOfPages());
        self::assertSame(6, $paginator->getKeyOfFirstPaginatedItem());
        self::assertSame(8, $paginator->getKeyOfLastPaginatedItem());
        self::assertCount(3, $paginator->getPaginatedItems());
    }

    /**
     * @test
     */
    public function paginatorProperlyCalculatesLastPage(): void
    {
        $paginator = new QueryResultPaginator(
            $this->postRepository->findAll(),
            5,
            3
        );

        self::assertSame(5, $paginator->getNumberOfPages());
        self::assertSame(12, $paginator->getKeyOfFirstPaginatedItem());
        self::assertSame(13, $paginator->getKeyOfLastPaginatedItem());
        self::assertCount(2, $paginator->getPaginatedItems());
    }

    /**
     * @test
     */
    public function withCurrentPageNumberThrowsInvalidArgumentExceptionIfCurrentPageIsLowerThanOne(): void
    {
        $this->expectExceptionCode(1573047338);

        $paginator = new QueryResultPaginator(
            $this->postRepository->findAll(),
            1,
            3
        );
        $paginator->withCurrentPageNumber(0);
    }

    /**
     * @test
     */
    public function paginatorSetsCurrentPageToLastPageIfCurrentPageExceedsMaximum(): void
    {
        $paginator = new QueryResultPaginator(
            $this->postRepository->findAll(),
            3,
            10
        );

        self::assertEquals(2, $paginator->getCurrentPageNumber());
        self::assertEquals(2, $paginator->getNumberOfPages());
        self::assertCount(4, $paginator->getPaginatedItems());
    }

    /**
     * @test
     */
    public function paginatorProperlyCalculatesOnlyOnePage(): void
    {
        $paginator = new QueryResultPaginator(
            $this->postRepository->findAll(),
            1,
            50
        );

        self::assertSame(1, $paginator->getNumberOfPages());
        self::assertSame(0, $paginator->getKeyOfFirstPaginatedItem());
        self::assertSame(13, $paginator->getKeyOfLastPaginatedItem());
        self::assertCount(14, $paginator->getPaginatedItems());
    }
}
