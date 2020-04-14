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

/**
 * An interface that defines methods needed to implement a paginator, i.e. an object that handles
 * a set of items and returns a sub set of items, given by a configuration.
 */
interface PaginatorInterface
{
    /**
     * Sets the amount of paginated items per page
     *
     * Must return a new instance of the Paginator with an updated internal state
     */
    public function withItemsPerPage(int $itemsPerPage): PaginatorInterface;

    /**
     * Sets the current page to calculate paginated items for
     *
     * Must return a new instance of the Paginator with an updated internal state
     */
    public function withCurrentPageNumber(int $currentPageNumber): PaginatorInterface;

    /**
     * Returns an iterable, sub set of the original set of items
     *
     * @return iterable
     */
    public function getPaginatedItems(): iterable;

    /**
     * Returns the total number of pages, given the total number of non paginated items and the
     * items per page configuration
     */
    public function getNumberOfPages(): int;

    /**
     * Returns the current page number
     */
    public function getCurrentPageNumber(): int;

    /**
     * Returns the key of the first paginated item
     *
     * This is useful to display the exact range of
     * items that are available via getPaginatedItems
     */
    public function getKeyOfFirstPaginatedItem(): int;

    /**
     * Returns the key of the last paginated item
     *
     * This is useful to display the exact range of
     * items that are available via getPaginatedItems
     */
    public function getKeyOfLastPaginatedItem(): int;
}
