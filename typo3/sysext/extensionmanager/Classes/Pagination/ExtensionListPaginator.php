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

namespace TYPO3\CMS\Extensionmanager\Pagination;

use TYPO3\CMS\Core\Pagination\AbstractPaginator;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * Paginates an extension list whose pages are fetched one at a time, so that
 * rendering a single page of the catalogue does not require every known
 * extension to be loaded first.
 *
 * The items are produced by a callable rather than by a query, because the
 * catalogue is not necessarily backed by a database:
 * {@see \TYPO3\CMS\Core\Pagination\QueryBuilderPaginator} would tie the list
 * views to a QueryBuilder and hand out raw rows instead of extensions.
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
final class ExtensionListPaginator extends AbstractPaginator
{
    /**
     * @var Extension[]
     */
    private array $paginatedItems = [];

    /**
     * @param \Closure(int, int): Extension[] $itemsFetcher called with offset and limit
     * @param int $totalAmountOfItems the number of items the fetcher would return without pagination
     */
    public function __construct(
        private readonly \Closure $itemsFetcher,
        private readonly int $totalAmountOfItems,
        int $currentPageNumber = 1,
        int $itemsPerPage = 10,
    ) {
        $this->setCurrentPageNumber($currentPageNumber);
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    /**
     * @return Extension[]
     */
    public function getPaginatedItems(): iterable
    {
        return $this->paginatedItems;
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        $this->paginatedItems = ($this->itemsFetcher)($offset, $itemsPerPage);
    }

    protected function getTotalAmountOfItems(): int
    {
        return $this->totalAmountOfItems;
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->paginatedItems);
    }
}
