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

namespace TYPO3\CMS\IndexedSearch\Pagination;

use TYPO3\CMS\Core\Pagination\AbstractPaginator;

/**
 * Stub pagination that contains only an already limited result set ("slice") and not all available results.
 *
 * @internal
 */
final class SlicePaginator extends AbstractPaginator
{
    protected array $items = [];
    protected int $totalAmount = 0;

    public function __construct(
        array $items,
        int $currentPageNumber = 1,
        int $totalAmount = 0,
        int $itemsPerPage = 10,
    ) {
        $this->items = $items;
        $this->totalAmount = $totalAmount;
        $this->setCurrentPageNumber($currentPageNumber);
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        // no-op
    }

    protected function getTotalAmountOfItems(): int
    {
        return $this->totalAmount;
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->items);
    }

    public function getPaginatedItems(): iterable
    {
        return $this->items;
    }
}
