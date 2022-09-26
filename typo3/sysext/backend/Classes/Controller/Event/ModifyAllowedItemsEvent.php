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

namespace TYPO3\CMS\Backend\Controller\Event;

/**
 * This event allows extensions to add or remove from the list of allowed link types.
 */
final class ModifyAllowedItemsEvent
{
    /**
     * @param string[] $allowedItems
     * @param array<string, mixed> $currentLinkParts
     */
    public function __construct(
        protected array $allowedItems,
        protected array $currentLinkParts,
    ) {
    }

    /**
     * @return string[]
     */
    public function getAllowedItems(): array
    {
        return $this->allowedItems;
    }

    public function addAllowedItem(string $item): self
    {
        $this->allowedItems[] = $item;
        return $this;
    }

    public function removeAllowedItem(string $new): self
    {
        $this->allowedItems = array_filter($this->allowedItems, static fn (string $item): bool => $item !== $new);
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCurrentLinkParts(): array
    {
        return $this->currentLinkParts;
    }
}
