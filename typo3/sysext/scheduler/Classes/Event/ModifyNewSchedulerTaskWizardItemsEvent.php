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

namespace TYPO3\CMS\Scheduler\Event;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Listeners to this event are able to modify the scheduler task items for the new task wizard
 */
final class ModifyNewSchedulerTaskWizardItemsEvent
{
    public function __construct(
        private array $wizardItems,
        private readonly ServerRequestInterface $request,
    ) {}

    public function getWizardItems(): array
    {
        return $this->wizardItems;
    }

    public function setWizardItems(array $wizardItems): void
    {
        $this->wizardItems = $wizardItems;
    }

    public function addWizardItem(string $key, array $wizardItem): void
    {
        $this->wizardItems[$key] = $wizardItem;
    }

    public function removeWizardItem(string $key): void
    {
        unset($this->wizardItems[$key]);
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
