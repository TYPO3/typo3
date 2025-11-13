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

namespace TYPO3\CMS\Backend\Localization;

/**
 * Registry for localization handlers
 *
 * Manages all available localization handlers and provides methods to
 * retrieve them by identifier or get all available handlers
 *
 * @internal
 */
class LocalizationHandlerRegistry
{
    /**
     * @var array<string, LocalizationHandlerInterface>
     */
    private array $handlers = [];

    /**
     * @param iterable<LocalizationHandlerInterface> $handlers
     */
    public function __construct(iterable $handlers)
    {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->getIdentifier()] = $handler;
        }
    }

    /**
     * Get a handler by its identifier
     *
     * @throws \InvalidArgumentException if handler not found
     */
    public function getHandler(string $identifier): LocalizationHandlerInterface
    {
        if (!$this->hasHandler($identifier)) {
            throw new \InvalidArgumentException(
                sprintf('Localization handler "%s" not found', $identifier),
                1733832000
            );
        }

        return $this->handlers[$identifier];
    }

    /**
     * Check if a handler with the given identifier exists
     */
    public function hasHandler(string $identifier): bool
    {
        return isset($this->handlers[$identifier]);
    }

    /**
     * Get available handlers for the given localization context
     *
     * Filters handlers based on their availability for the specific context.
     *
     * @return array<string, LocalizationHandlerInterface> Available handlers indexed by identifier
     */
    public function getAvailableHandlers(LocalizationInstructions $instructions): array
    {
        $availableHandlers = [];
        foreach ($this->handlers as $handler) {
            if ($handler->isAvailable($instructions)) {
                $availableHandlers[$handler->getIdentifier()] = $handler;
            }
        }

        return $availableHandlers;
    }
}
