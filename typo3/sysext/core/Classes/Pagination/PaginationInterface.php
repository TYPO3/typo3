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
 * An interface that defines methods needed to implement a pagination
 *
 * A pagination is an object that takes a paginator and calculates variables
 * to render a pagination for the paginated objects in the given paginator
 */
interface PaginationInterface
{
    public function __construct(PaginatorInterface $paginator);

    /**
     * Must return the previous page number
     *
     * Is allowed to return null to indicate that there is no
     * previous page, e.g. when being on the first page
     */
    public function getPreviousPageNumber(): ?int;

    /**
     * Must return the next page number
     *
     * Is allowed to return null to indicate that there is no
     * next page, e.g. when being on the last page
     */
    public function getNextPageNumber(): ?int;

    /**
     * Must return the first page number, usually this will return 1
     */
    public function getFirstPageNumber(): int;

    /**
     * Must return the last page number, usually this will return the total amount of pages
     */
    public function getLastPageNumber(): int;

    /**
     * Must return the human readable index of the first paginated item
     *
     * Example: given a set of 10 total items, 5 items per page and the current page being 2,
     * the start record number is 6:
     *
     * Page 1: Records 1-5
     * Page 2: Records 6-10
     */
    public function getStartRecordNumber(): int;

    /**
     * Must return the human readable index of the last paginated item
     *
     * Example: given a set of 10 total items, 5 items per page and the current page being 2,
     * the end record number is 10.
     *
     * Page 1: Records 1-5
     * Page 2: Records 6-10
     */
    public function getEndRecordNumber(): int;
}
