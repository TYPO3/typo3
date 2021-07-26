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

final class SlidingWindowPagination implements PaginationInterface
{
    protected int $displayRangeStart = 0;
    protected int $displayRangeEnd = 0;
    protected bool $hasLessPages = false;
    protected bool $hasMorePages = false;
    protected int $maximumNumberOfLinks = 0;
    protected PaginatorInterface $paginator;

    public function __construct(PaginatorInterface $paginator, int $maximumNumberOfLinks = 0)
    {
        $this->paginator = $paginator;

        if ($maximumNumberOfLinks > 0) {
            $this->maximumNumberOfLinks = $maximumNumberOfLinks;
        }

        $this->calculateDisplayRange();
    }

    public function getPreviousPageNumber(): ?int
    {
        $previousPage = $this->paginator->getCurrentPageNumber() - 1;

        if ($previousPage > $this->paginator->getNumberOfPages()) {
            return null;
        }

        return $previousPage >= $this->getFirstPageNumber() ? $previousPage : null;
    }

    public function getNextPageNumber(): ?int
    {
        $nextPage = $this->paginator->getCurrentPageNumber() + 1;

        return $nextPage <= $this->paginator->getNumberOfPages() ? $nextPage : null;
    }

    public function getFirstPageNumber(): int
    {
        return 1;
    }

    public function getLastPageNumber(): int
    {
        return $this->paginator->getNumberOfPages();
    }

    public function getStartRecordNumber(): int
    {
        if ($this->paginator->getCurrentPageNumber() > $this->paginator->getNumberOfPages()) {
            return 0;
        }

        return $this->paginator->getKeyOfFirstPaginatedItem() + 1;
    }

    public function getEndRecordNumber(): int
    {
        if ($this->paginator->getCurrentPageNumber() > $this->paginator->getNumberOfPages()) {
            return 0;
        }

        return $this->paginator->getKeyOfLastPaginatedItem() + 1;
    }

    public function getAllPageNumbers(): array
    {
        return range($this->displayRangeStart, $this->displayRangeEnd);
    }

    public function getDisplayRangeStart(): int
    {
        return $this->displayRangeStart;
    }

    public function getDisplayRangeEnd(): int
    {
        return $this->displayRangeEnd;
    }

    public function getHasLessPages(): bool
    {
        return $this->hasLessPages;
    }

    public function getHasMorePages(): bool
    {
        return $this->hasMorePages;
    }

    public function getMaximumNumberOfLinks(): int
    {
        return $this->maximumNumberOfLinks;
    }

    public function getPaginator(): PaginatorInterface
    {
        return $this->paginator;
    }

    protected function calculateDisplayRange(): void
    {
        $maximumNumberOfLinks = $this->maximumNumberOfLinks;
        $numberOfPages = $this->paginator->getNumberOfPages();

        if ($maximumNumberOfLinks > $numberOfPages) {
            $maximumNumberOfLinks = $numberOfPages;
        }

        $currentPage = $this->paginator->getCurrentPageNumber();
        $delta = floor($maximumNumberOfLinks / 2);

        $this->displayRangeStart = (int)($currentPage - $delta);
        $this->displayRangeEnd = (int)($currentPage + $delta - ($maximumNumberOfLinks % 2 === 0 ? 1 : 0));

        if ($this->displayRangeStart < 1) {
            $this->displayRangeEnd -= $this->displayRangeStart - 1;
        }

        if ($this->displayRangeEnd > $numberOfPages) {
            $this->displayRangeStart -= $this->displayRangeEnd - $numberOfPages;
        }

        $this->displayRangeStart = (int)max($this->displayRangeStart, 1);
        $this->displayRangeEnd = (int)min($this->displayRangeEnd, $numberOfPages);
        $this->hasLessPages = $this->displayRangeStart > 2;
        $this->hasMorePages = $this->displayRangeEnd + 1 < $this->paginator->getNumberOfPages();
    }
}
