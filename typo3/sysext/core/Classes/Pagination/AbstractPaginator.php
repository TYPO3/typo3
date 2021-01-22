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

abstract class AbstractPaginator implements PaginatorInterface
{
    /**
     * @var int
     */
    protected $numberOfPages = 1;

    /**
     * @var int
     */
    protected $keyOfFirstPaginatedItem = 0;

    /**
     * @var int
     */
    protected $keyOfLastPaginatedItem = 0;

    /**
     * @var int
     */
    private $currentPageNumber = 1;

    /**
     * @var int
     */
    private $itemsPerPage = 10;

    public function withItemsPerPage(int $itemsPerPage): PaginatorInterface
    {
        if ($itemsPerPage === $this->itemsPerPage) {
            return $this;
        }

        $new = clone $this;
        $new->setItemsPerPage($itemsPerPage);
        $new->updateInternalState();

        return $new;
    }

    public function withCurrentPageNumber(int $currentPageNumber): PaginatorInterface
    {
        if ($currentPageNumber === $this->currentPageNumber) {
            return $this;
        }

        $new = clone $this;
        $new->setCurrentPageNumber($currentPageNumber);
        $new->updateInternalState();

        return $new;
    }

    public function getNumberOfPages(): int
    {
        return $this->numberOfPages;
    }

    public function getCurrentPageNumber(): int
    {
        return $this->currentPageNumber;
    }

    public function getKeyOfFirstPaginatedItem(): int
    {
        return $this->keyOfFirstPaginatedItem;
    }

    public function getKeyOfLastPaginatedItem(): int
    {
        return $this->keyOfLastPaginatedItem;
    }

    /**
     * Must update the paginated items, i.e. the subset of all items, limited and defined by
     * the given amount of items per page and offset
     */
    abstract protected function updatePaginatedItems(int $itemsPerPage, int $offset): void;

    /**
     * Must return the total amount of all unpaginated items
     */
    abstract protected function getTotalAmountOfItems(): int;

    /**
     * Must return the amount of paginated items on the current page
     */
    abstract protected function getAmountOfItemsOnCurrentPage(): int;

    /**
     * States whether there are items on the current page
     */
    protected function hasItemsOnCurrentPage(): bool
    {
        return $this->getAmountOfItemsOnCurrentPage() > 0;
    }

    /**
     * This method is the heart of the pagination. It updates all internal params and then calls the
     * {@see updatePaginatedItems} method which must update the set of paginated items.
     */
    protected function updateInternalState(): void
    {
        $offset = (int)($this->itemsPerPage * ($this->currentPageNumber - 1));
        $totalAmountOfItems = $this->getTotalAmountOfItems();

        /*
         * If the total amount of items is zero, then the number of pages is mathematically zero as
         * well. As that looks strange in the frontend, the number of pages is forced to be at least
         * one.
         */
        $this->numberOfPages = max(1, (int)ceil($totalAmountOfItems / $this->itemsPerPage));

        /*
         * To prevent empty results in case the given current page number exceeds the maximum number
         * of pages, we set the current page number to the last page and update the internal state
         * with this value again. Such situation should in the first place be prevented by not allowing
         * those values to be passed, e.g. by using the "max" attribute in the view. However there are
         * valid cases. For example when a user deletes a record while the pagination is already visible
         * to another user with, until then, a valid "max" value. Passing invalid values unintentionally
         * should therefore just silently be resolved.
         */
        if ($this->currentPageNumber > $this->numberOfPages) {
            $this->currentPageNumber = $this->numberOfPages;
            $this->updateInternalState();
            return;
        }

        $this->updatePaginatedItems($this->itemsPerPage, $offset);

        if (!$this->hasItemsOnCurrentPage()) {
            $this->keyOfFirstPaginatedItem = 0;
            $this->keyOfLastPaginatedItem = 0;
            return;
        }

        $indexOfLastPaginatedItem = min($offset + $this->itemsPerPage, $totalAmountOfItems);

        $this->keyOfFirstPaginatedItem = $offset;
        $this->keyOfLastPaginatedItem = $indexOfLastPaginatedItem - 1;
    }

    protected function setItemsPerPage(int $itemsPerPage): void
    {
        if ($itemsPerPage < 1) {
            throw new \InvalidArgumentException(
                'Argument $itemsPerPage must be greater than 0',
                1573061766
            );
        }

        $this->itemsPerPage = $itemsPerPage;
    }

    protected function setCurrentPageNumber(int $currentPageNumber): void
    {
        if ($currentPageNumber < 1) {
            throw new \InvalidArgumentException(
                'Argument $currentPageNumber must be greater than 0',
                1573047338
            );
        }

        $this->currentPageNumber = $currentPageNumber;
    }
}
