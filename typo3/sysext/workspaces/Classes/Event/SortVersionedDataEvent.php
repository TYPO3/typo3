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

namespace TYPO3\CMS\Workspaces\Event;

use TYPO3\CMS\Workspaces\Service\GridDataService;

/**
 * Used in the workspaces module after sorting all data for versions of a workspace.
 */
final class SortVersionedDataEvent
{
    /**
     * @var GridDataService
     */
    private $gridService;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $sortColumn;

    /**
     * @var string
     */
    private $sortDirection;

    public function __construct(GridDataService $gridService, array $data, string $sortColumn, string $sortDirection)
    {
        $this->gridService = $gridService;
        $this->data = $data;
        $this->sortColumn = $sortColumn;
        $this->sortDirection = $sortDirection;
    }

    public function getGridService(): GridDataService
    {
        return $this->gridService;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getSortColumn(): string
    {
        return $this->sortColumn;
    }

    public function setSortColumn(string $sortColumn): void
    {
        $this->sortColumn = $sortColumn;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    public function setSortDirection(string $sortDirection): void
    {
        $this->sortDirection = $sortDirection;
    }
}
