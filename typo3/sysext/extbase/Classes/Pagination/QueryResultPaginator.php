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

namespace TYPO3\CMS\Extbase\Pagination;

use TYPO3\CMS\Core\Pagination\AbstractPaginator;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

final class QueryResultPaginator extends AbstractPaginator
{
    /**
     * @var QueryResultInterface
     */
    private $queryResult;

    /**
     * @var QueryResultInterface
     */
    private $paginatedQueryResult;

    public function __construct(
        QueryResultInterface $queryResult,
        int $currentPageNumber = 1,
        int $itemsPerPage = 10
    ) {
        $this->queryResult = $queryResult;
        $this->setCurrentPageNumber($currentPageNumber);
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    /**
     * @return iterable|QueryResultInterface
     */
    public function getPaginatedItems(): iterable
    {
        return $this->paginatedQueryResult;
    }

    protected function updatePaginatedItems(int $limit, int $offset): void
    {
        $this->paginatedQueryResult = $this->queryResult
            ->getQuery()
            ->setLimit($limit)
            ->setOffset($offset)
            ->execute();
    }

    protected function getTotalAmountOfItems(): int
    {
        return count($this->queryResult);
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->paginatedQueryResult);
    }
}
