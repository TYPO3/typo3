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
 * This event allows extensions to modify the list of link handlers and their configuration before they are invoked.
 */
final class ModifyLinkHandlersEvent
{
    /**
     * @param array<string, array> $linkHandlers
     * @param array<string, mixed> $currentLinkParts
     */
    public function __construct(
        protected array $linkHandlers,
        protected array $currentLinkParts,
    ) {}

    /**
     * @return array<string, array>
     */
    public function getLinkHandlers(): array
    {
        return $this->linkHandlers;
    }

    /**
     * Gets an individual handler by name.
     *
     * @param string $name The handler name, including trailing period.
     * @return array<string, mixed>|null The handler definition, or null if not defined.
     */
    public function getLinkHandler(string $name): ?array
    {
        return $this->linkHandlers[$name] ?? null;
    }

    /**
     * Sets a handler by name, overwriting it if it already exists.
     *
     * @param string $name The handler name, including trailing period.
     * @param array<string, mixed> $handler
     * @return $this
     */
    public function setLinkHandler(string $name, array $handler): self
    {
        $this->linkHandlers[$name] = $handler;
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
