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

namespace TYPO3\CMS\Extensionmanager\Event;

/**
 * Event that is triggered when rendering an additional action (currently within a Fluid ViewHelper).
 */
final class AvailableActionsForExtensionEvent
{
    /**
     * @var string
     */
    private $packageKey;

    /**
     * @var array
     */
    private $packageData;

    /**
     * @var string[]
     */
    private $actions;

    public function __construct(string $packageKey, array $packageData, array $actions)
    {
        $this->packageKey = $packageKey;
        $this->packageData = $packageData;
        $this->actions = $actions;
    }

    public function getPackageKey(): string
    {
        return $this->packageKey;
    }

    public function getPackageData(): array
    {
        return $this->packageData;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function addAction(string $actionKey, string $content): void
    {
        $this->actions[$actionKey] = $content;
    }

    public function setActions(array $actions): void
    {
        $this->actions = $actions;
    }
}
