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

namespace TYPO3\CMS\Webhooks\Pagination;

use TYPO3\CMS\Core\Pagination\AbstractPaginator;

/**
 * A custom Paginator for dealing with the demand object.
 *
 * @internal not part of TYPO3's Core API
 * @todo should be replaced with the regular ArrayPaginator
 */
final class DemandedArrayPaginator extends AbstractPaginator
{
    private array $items;
    private int $allCount;

    private array $paginatedItems = [];

    public function __construct(
        array $items,
        int $currentPageNumber = 1,
        int $itemsPerPage = 10,
        int $allCount = 0
    ) {
        $this->items = $items;
        $this->setCurrentPageNumber($currentPageNumber);
        $this->setItemsPerPage($itemsPerPage);
        $this->allCount = $allCount;

        $this->updateInternalState();
    }

    public function getPaginatedItems(): iterable
    {
        return $this->paginatedItems;
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        $this->paginatedItems = $this->items;
    }

    protected function getTotalAmountOfItems(): int
    {
        return $this->allCount;
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->paginatedItems);
    }
}
