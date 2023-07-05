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

namespace TYPO3\CMS\Core\Authentication\Mfa;

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;

/**
 * Registry for configuration providers which is called by the ConfigurationProviderPass
 *
 * @internal should only be used by the TYPO3 Core
 */
class MfaProviderRegistry
{
    /**
     * @var MfaProviderManifestInterface[]
     */
    protected array $providers = [];

    public function registerProvider(MfaProviderManifestInterface $provider): void
    {
        $this->providers[$provider->getIdentifier()] = $provider;
    }

    public function hasProvider(string $identifier): bool
    {
        return isset($this->providers[$identifier]);
    }

    public function hasProviders(): bool
    {
        return $this->providers !== [];
    }

    public function getProvider(string $identifier): MfaProviderManifestInterface
    {
        if (!$this->hasProvider($identifier)) {
            throw new \InvalidArgumentException('No MFA provider for identifier ' . $identifier . ' found.', 1610994735);
        }
        return $this->providers[$identifier];
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Whether the given user has active providers
     */
    public function hasActiveProviders(AbstractUserAuthentication $user): bool
    {
        return $this->getActiveProviders($user) !== [];
    }

    /**
     * Get all active providers for the given user
     *
     * @return MfaProviderManifestInterface[]
     */
    public function getActiveProviders(AbstractUserAuthentication $user): array
    {
        return array_filter($this->providers, static function ($provider) use ($user) {
            return $provider->isActive(MfaProviderPropertyManager::create($provider, $user));
        });
    }

    /**
     * Get the first provider for the user which can be used for authentication.
     * This is either the user specified default provider, or the first active
     * provider based on the providers configured ordering.
     *
     * @return MfaProviderManifestInterface
     */
    public function getFirstAuthenticationAwareProvider(AbstractUserAuthentication $user): ?MfaProviderManifestInterface
    {
        $activeProviders = $this->getActiveProviders($user);
        // If the user did not activate any provider yet, authentication is not possible
        if ($activeProviders === []) {
            return null;
        }
        // Check if the user has chosen a default (preferred) provider, which is still active
        $defaultProvider = (string)($user->uc['mfa']['defaultProvider'] ?? '');
        if ($defaultProvider !== '' && isset($activeProviders[$defaultProvider])) {
            return $activeProviders[$defaultProvider];
        }
        // If no default provider exists or is not valid, return the first active provider
        return array_shift($activeProviders);
    }

    /**
     * Whether the given user has locked providers
     */
    public function hasLockedProviders(AbstractUserAuthentication $user): bool
    {
        return $this->getLockedProviders($user) !== [];
    }

    /**
     * Get all locked providers for the given user
     *
     * @return MfaProviderManifestInterface[]
     */
    public function getLockedProviders(AbstractUserAuthentication $user): array
    {
        return array_filter($this->providers, static function ($provider) use ($user) {
            return $provider->isLocked(MfaProviderPropertyManager::create($provider, $user));
        });
    }

    public function allowedProvidersItemsProcFunc(array &$parameters): void
    {
        foreach ($this->providers as $provider) {
            $parameters['items'][] = [
                'label' => $provider->getTitle(),
                'value' => $provider->getIdentifier(),
                'icon' => $provider->getIconIdentifier(),
                'description' => $provider->getDescription(),
            ];
        }
    }
}
