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

namespace TYPO3\CMS\Core\Pagination;

use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Provides a paginator implementation to be used with {@see QueryBuilder} as
 * data source.
 *
 * **Be aware** that this comes with a couple of things to be considered:
 *
 * * QueryBuilder is used in a generic way by this Paginator and does not take care of proper language overlay
 *   handling and cannot do that in a easy way and applying overlays on the result set can lead to weired item
 *   count jumps on pages in case some of them are removed. For example 5 items on page 1, 6 on page two albeit
 *   10 items per page has been requested.
 *
 * * The paginator is completely in charge handling the pagination (offset/limit) and **does** not take
 *   existing constraints of the passed QueryBuilder into account to match the expectation shared across
 *   pagination handling throughout different frameworks and other Paginator implementation of TYPO3.
 */
final class QueryBuilderPaginator extends AbstractPaginator
{
    private array $paginatedItems = [];
    private ?int $totalItems = null;

    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        int $currentPageNumber = 1,
        int $itemsPerPage = 10,
    ) {
        $this->setCurrentPageNumber($currentPageNumber);
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    public function getPaginatedItems(): iterable
    {
        return $this->paginatedItems;
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        $paginatedQueryBuilder = clone $this->queryBuilder;
        $this->paginatedItems = $paginatedQueryBuilder
            ->setMaxResults($itemsPerPage)
            ->setFirstResult($offset)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    protected function getTotalAmountOfItems(): int
    {
        return $this->getTotalItems();
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->paginatedItems);
    }

    private function getTotalItems(): int
    {
        if ($this->totalItems === null) {
            $clonedQueryBuilder = clone $this->queryBuilder;
            // Remove obsolete query parts. There is no need to enforce any ordering improving
            // the performance and pagination constraints (LIMIT and OFFSET) are removed because
            // otherwise we would not get the total items count.
            $clonedQueryBuilder
                ->resetOrderBy()
                ->setMaxResults(null)
                ->setFirstResult(0);

            $this->totalItems = (int)$clonedQueryBuilder->getConnection()->createQueryBuilder()
                // @todo Upstream doctrine/dbal with() is not adopted in the decoration pattern and the reason to use
                //       typo3 internal implementation for the common table expression here. Replace it when upstream
                //       with() support has been integrated into the decoration chain.
                ->typo3_with('cte_count', $clonedQueryBuilder)
                ->count('*')
                ->from('cte_count')
                ->setParameters($clonedQueryBuilder->getParameters(), $clonedQueryBuilder->getParameterTypes())
                ->executeQuery()
                ->fetchOne();
        }
        return $this->totalItems;
    }
}
