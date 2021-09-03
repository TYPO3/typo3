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

/**
 * An event to modify the clear cache actions, shown in the TYPO3 Backend top toolbar
 */
final class ModifyClearCacheActionsEvent
{
    private array $cacheActions;
    private array $cacheActionIdentifiers;

    public function __construct(array $cacheActions, array $cacheActionIdentifiers)
    {
        $this->cacheActions = $cacheActions;
        $this->cacheActionIdentifiers = $cacheActionIdentifiers;
    }

    public function addCacheAction(array $cacheAction): void
    {
        $this->cacheActions[] = $cacheAction;
    }

    public function setCacheActions(array $cacheActions): void
    {
        $this->cacheActions = $cacheActions;
    }

    public function getCacheActions(): array
    {
        return $this->cacheActions;
    }

    public function addCacheActionIdentifier(string $cacheActionIdentifier): void
    {
        $this->cacheActionIdentifiers[] = $cacheActionIdentifier;
    }

    public function setCacheActionIdentifiers(array $cacheActionIdentifiers): void
    {
        $this->cacheActionIdentifiers = $cacheActionIdentifiers;
    }

    public function getCacheActionIdentifiers(): array
    {
        return $this->cacheActionIdentifiers;
    }
}
