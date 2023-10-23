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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Listeners to this event will be able to modify the prepared page tree items for the page tree
 */
final class AfterPageTreeItemsPreparedEvent
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        private readonly ServerRequestInterface $request,
        private array $items
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
