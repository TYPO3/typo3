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
 * Listeners to this Event will be able to modify the wizard items of the new content element wizard component
 */
final class ModifyNewContentElementWizardItemsEvent
{
    public function __construct(
        private array $wizardItems,
        private readonly array $pageInfo,
        private readonly int|null $colPos,
        private readonly int $sys_language,
        private readonly int $uid_pid,
    ) {
    }

    public function getWizardItems(): array
    {
        return $this->wizardItems;
    }

    public function setWizardItems(array $wizardItems): void
    {
        $this->wizardItems = $wizardItems;
    }

    public function hasWizardItem(string $identifier): bool
    {
        return isset($this->wizardItems[$identifier]);
    }

    public function getWizardItem(string $identifier): ?array
    {
        return $this->wizardItems[$identifier] ?? null;
    }

    /**
     * Add a new wizard item with configuration at a defined position.
     * Can also be used to relocate existing items and to modify their configuration.
     */
    public function setWizardItem(string $identifier, array $configuration, array $position = []): void
    {
        if (isset($this->wizardItems[$position['before'] ?? null])
            || isset($this->wizardItems[$position['after'] ?? null])
        ) {
            // Always unset an existing item if valid positioning is requested
            unset($this->wizardItems[$identifier]);
        }

        // Add item before another item
        if (($position['before'] ?? false)
            && ($insertPosition = array_search((string)$position['before'], array_keys($this->wizardItems), true)) !== false
        ) {
            $this->wizardItems = array_slice($this->wizardItems, 0, $insertPosition)
                + [$identifier => $configuration]
                + array_slice($this->wizardItems, $insertPosition);
            return;
        }

        // Add item after another item
        if (($position['after'] ?? false)
            && ($insertPosition = array_search((string)$position['after'], array_keys($this->wizardItems), true)) !== false
        ) {
            $this->wizardItems = array_slice($this->wizardItems, 0, $insertPosition + 1)
                + [$identifier => $configuration]
                + array_slice($this->wizardItems, $insertPosition + 1);
            return;
        }

        // By default, add the item at the bottom or might just overwrite configuration of an existing item
        $this->wizardItems[$identifier] = $configuration;
    }

    public function removeWizardItem(string $identifier): bool
    {
        if (!$this->hasWizardItem($identifier)) {
            return false;
        }

        unset($this->wizardItems[$identifier]);
        return true;
    }

    /**
     * Provides information about the current page making use of the wizard.
     */
    public function getPageInfo(): array
    {
        return $this->pageInfo;
    }

    /**
     * Provides information about the column position of the button that triggered the wizard.
     */
    public function getColPos(): ?int
    {
        return $this->colPos;
    }

    /**
     * Provides information about the language used while triggering the wizard.
     */
    public function getSysLanguage(): int
    {
        return $this->sys_language;
    }

    /**
     * Provides information about the element to position the new element after (uid) or into (pid).
     */
    public function getUidPid(): int
    {
        return $this->uid_pid;
    }
}
