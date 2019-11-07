<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Pagination;

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

final class ArrayPaginator extends AbstractPaginator
{
    /**
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $paginatedItems = [];

    public function __construct(
        array $items,
        int $itemsPerPage = 10,
        int $currentPageNumber = 1
    ) {
        $this->items = $items;
        $this->setItemsPerPage($itemsPerPage);
        $this->setCurrentPageNumber($currentPageNumber);

        $this->updateInternalState();
    }

    /**
     * @return iterable|array
     */
    public function getPaginatedItems(): iterable
    {
        return $this->paginatedItems;
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        $this->paginatedItems = array_slice($this->items, $offset, $itemsPerPage);
    }

    protected function getTotalAmountOfItems(): int
    {
        return count($this->items);
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->paginatedItems);
    }
}
