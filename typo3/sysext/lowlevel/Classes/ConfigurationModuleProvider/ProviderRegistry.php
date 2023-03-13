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

namespace TYPO3\CMS\Lowlevel\ConfigurationModuleProvider;

/**
 * Registry for configuration providers which is called by the ConfigurationProviderPass
 */
final class ProviderRegistry
{
    /**
     * @var ProviderInterface[]
     */
    protected array $providers = [];

    public function registerProvider(ProviderInterface $provider, array $attributes): void
    {
        $this->providers[$attributes['identifier']] = $provider($attributes);
    }

    public function hasProvider(string $identifier): bool
    {
        return isset($this->providers[$identifier]);
    }

    public function getProvider(string $identifier): ProviderInterface
    {
        if (!isset($this->providers[$identifier])) {
            throw new \InvalidArgumentException('No provider for identifier ' . $identifier . 'found.', 1606490398);
        }

        return $this->providers[$identifier];
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    public function getFirstProvider(): ?ProviderInterface
    {
        return $this->providers !== [] ? reset($this->providers) : null;
    }
}
