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

namespace TYPO3\CMS\Core\Tests\Unit\Pagination;

use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SimplePaginationTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $paginator = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->paginator = new ArrayPaginator(range(1, 14, 1));
    }

    /**
     * @test
     */
    public function checkSimplePaginationWithAPaginatorWithDefaultSettings()
    {
        $pagination = new SimplePagination($this->paginator);

        self::assertSame(1, $pagination->getStartRecordNumber());
        self::assertSame(10, $pagination->getEndRecordNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(2, $pagination->getLastPageNumber());
        self::assertNull($pagination->getPreviousPageNumber());
        self::assertSame(2, $pagination->getNextPageNumber());
    }

    /**
     * @test
     */
    public function checkSimplePaginationWithAnIncreasedCurrentPageNumber()
    {
        $paginator = $this->paginator
            ->withCurrentPageNumber(2)
        ;

        $pagination = new SimplePagination($paginator);

        self::assertSame(11, $pagination->getStartRecordNumber());
        self::assertSame(14, $pagination->getEndRecordNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(2, $pagination->getLastPageNumber());
        self::assertSame(1, $pagination->getPreviousPageNumber());
        self::assertNull($pagination->getNextPageNumber());
    }

    /**
     * @test
     */
    public function checkSimplePaginationWithAnIncreasedCurrentPageNumberAndItemsPerPage()
    {
        $paginator = $this->paginator
            ->withItemsPerPage(3)
            ->withCurrentPageNumber(2)
        ;
        $pagination = new SimplePagination($paginator);

        self::assertSame(4, $pagination->getStartRecordNumber());
        self::assertSame(6, $pagination->getEndRecordNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(5, $pagination->getLastPageNumber());
        self::assertSame(1, $pagination->getPreviousPageNumber());
        self::assertSame(3, $pagination->getNextPageNumber());
    }

    /**
     * @test
     */
    public function checkPaginationWithAPaginatorThatOnlyHasOnePage()
    {
        $paginator = $this->paginator
            ->withItemsPerPage(50)
        ;
        $pagination = new SimplePagination($paginator);

        self::assertSame(1, $pagination->getStartRecordNumber());
        self::assertSame(14, $pagination->getEndRecordNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(1, $pagination->getLastPageNumber());
        self::assertNull($pagination->getPreviousPageNumber());
        self::assertNull($pagination->getNextPageNumber());
    }

    /**
     * @test
     */
    public function checkWithPaginatorWhoseCurrentPageIsOutOfBounds()
    {
        $paginator = $this->paginator
            ->withItemsPerPage(5)
            ->withCurrentPageNumber(100)
        ;
        $pagination = new SimplePagination($paginator);

        self::assertSame(0, $pagination->getStartRecordNumber());
        self::assertSame(0, $pagination->getEndRecordNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(3, $pagination->getLastPageNumber());
        self::assertNull($pagination->getPreviousPageNumber());
        self::assertNull($pagination->getNextPageNumber());
    }
}
