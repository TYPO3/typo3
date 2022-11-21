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

namespace TYPO3\CMS\Filelist\Pagination;

use TYPO3\CMS\Core\Pagination\AbstractPaginator;
use TYPO3\CMS\Filelist\Dto\ResourceCollection;

/**
 * @internal
 */
final class ResourceCollectionPaginator extends AbstractPaginator
{
    private ResourceCollection $paginatedItems;

    public function __construct(
        private readonly ResourceCollection $items,
        int $currentPageNumber = 1,
        int $itemsPerPage = 10
    ) {
        $this->paginatedItems = new ResourceCollection();
        $this->setCurrentPageNumber($currentPageNumber);
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    public function getPaginatedItems(): ResourceCollection
    {
        return $this->paginatedItems;
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        $this->paginatedItems = new ResourceCollection(array_slice($this->items->getResources(), $offset, $itemsPerPage));
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
