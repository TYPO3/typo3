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

namespace TYPO3\CMS\Backend\Backend\Event;

use TYPO3\CMS\Backend\Backend\ToolbarItems\ClearCacheToolbarItem;

/**
 * An event to modify the clear cache actions, shown in the TYPO3 Backend top toolbar
 *
 * @phpstan-import-type CacheAction from ClearCacheToolbarItem
 */
final class ModifyClearCacheActionsEvent
{
    /**
     * @param list<CacheAction> $cacheActions
     * @param list<non-empty-string> $cacheActionIdentifiers
     */
    public function __construct(private array $cacheActions, private array $cacheActionIdentifiers) {}

    /**
     * @param CacheAction $cacheAction
     */
    public function addCacheAction(array $cacheAction): void
    {
        $this->cacheActions[] = $cacheAction;
    }

    /**
     * @param list<CacheAction> $cacheActions
     */
    public function setCacheActions(array $cacheActions): void
    {
        $this->cacheActions = $cacheActions;
    }

    /**
     * @return list<CacheAction>
     */
    public function getCacheActions(): array
    {
        return $this->cacheActions;
    }

    /**
     * @param non-empty-string $cacheActionIdentifier
     */
    public function addCacheActionIdentifier(string $cacheActionIdentifier): void
    {
        $this->cacheActionIdentifiers[] = $cacheActionIdentifier;
    }

    /**
     * @param list<non-empty-string> $cacheActionIdentifiers
     */
    public function setCacheActionIdentifiers(array $cacheActionIdentifiers): void
    {
        $this->cacheActionIdentifiers = $cacheActionIdentifiers;
    }

    /**
     * @return list<non-empty-string>
     */
    public function getCacheActionIdentifiers(): array
    {
        return $this->cacheActionIdentifiers;
    }
}
