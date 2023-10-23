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

namespace TYPO3\CMS\Frontend\Event;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Listeners to this Event will be able to modify items for a menu generated with HMENU
 */
final class FilterMenuItemsEvent
{
    public function __construct(
        private readonly array $allMenuItems,
        private array $filteredMenuItems,
        private readonly array $menuConfiguration,
        private readonly array $itemConfiguration,
        private readonly array $bannedMenuItems,
        private readonly array $excludedDoktypes,
        private readonly Site $site,
        private readonly Context $context,
        private readonly array $currentPage
    ) {}

    public function getAllMenuItems(): array
    {
        return $this->allMenuItems;
    }

    public function getFilteredMenuItems(): array
    {
        return $this->filteredMenuItems;
    }

    public function setFilteredMenuItems(array $filteredMenuItems): void
    {
        $this->filteredMenuItems = $filteredMenuItems;
    }

    public function getMenuConfiguration(): array
    {
        return $this->menuConfiguration;
    }

    public function getItemConfiguration(): array
    {
        return $this->itemConfiguration;
    }

    public function getBannedMenuItems(): array
    {
        return $this->bannedMenuItems;
    }

    public function getExcludedDoktypes(): array
    {
        return $this->excludedDoktypes;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCurrentPage(): array
    {
        return $this->currentPage;
    }
}
