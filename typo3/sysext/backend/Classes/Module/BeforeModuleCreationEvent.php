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

namespace TYPO3\CMS\Backend\Module;

/**
 * Listeners can adjust the module configuration before the module gets created and registered
 */
final class BeforeModuleCreationEvent
{
    public function __construct(private readonly string $identifier, private array $configuration) {}

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function hasConfigurationValue(string $key): bool
    {
        return isset($this->configuration[$key]);
    }

    public function getConfigurationValue(string $key, mixed $default = null): mixed
    {
        return $this->configuration[$key] ?? $default;
    }

    public function setConfigurationValue(string $key, mixed $value): void
    {
        $this->configuration[$key] = $value;
    }
}
