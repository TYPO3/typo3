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

namespace TYPO3\CMS\Dashboard\Widgets;

use TYPO3\CMS\Core\Pagination\PaginatorInterface;

/**
 * The data provider of a ListWidget can implement this interface instead of
 * {@see ListDataProviderInterface} to let the widget render a paginator.
 */
interface PaginatedListDataProviderInterface
{
    /**
     * Return a paginator for the requested page, showing the given number of items per page.
     * The number of items per page is configured in the widget and can be changed by editors.
     * The paginator corrects page numbers that exceed the number of available pages.
     */
    public function getPaginator(int $currentPage, int $itemsPerPage): PaginatorInterface;
}
