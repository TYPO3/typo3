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

namespace TYPO3\CMS\Backend\LoginProvider;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Login Providers for the TYPO3 Backend can be registered via
 * $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders']
 *
 * This makes it possible to e.g. register a login provider to log-in via a common SSO login,
 * with a different entry point.
 *
 * By default, TYPO3 ships with a username/password login provider.
 *
 * This class is responsible to fetch the login providers sorted by their priority, and encapsulate
 * the logic further.
 *
 * @internal This resolver class itself is not part of the TYPO3 Core API as it is only used in TYPO3's Backend login.
 */
readonly class LoginProviderResolver
{
    /**
     * Get all registered login providers in correct order
     */
    public function getLoginProviders(): array
    {
        return $this->getValidatedAndSortedProviders();
    }

    public function getLoginProviderConfigurationByIdentifier(string $identifier): array
    {
        return $this->getValidatedAndSortedProviders()[$identifier] ?? [];
    }

    /**
     * Return the identifier of the first login provider / with the highest priority.
     */
    public function getPrimaryLoginProviderIdentifier(): string
    {
        return (string)key($this->getValidatedAndSortedProviders());
    }

    /**
     * Fetch the login provider identifier from request, check for POST Body, then Query Param, and then checks for the cookie.
     */
    public function resolveLoginProviderIdentifierFromRequest(ServerRequestInterface $request, string $cookieName): string
    {
        $loginProvider = (string)($request->getParsedBody()['loginProvider'] ?? $request->getQueryParams()['loginProvider'] ?? '');
        if ((empty($loginProvider) || !$this->hasLoginProvider($loginProvider)) && !empty($request->getCookieParams()[$cookieName] ?? null)) {
            $loginProvider = $request->getCookieParams()[$cookieName];
        }
        if (empty($loginProvider) || !$this->hasLoginProvider($loginProvider)) {
            $loginProvider = $this->getPrimaryLoginProviderIdentifier();
        }
        return (string)$loginProvider;
    }

    /**
     * Check if the login provider is registered.
     */
    protected function hasLoginProvider(string $identifier): bool
    {
        return isset($this->getValidatedAndSortedProviders()[$identifier]);
    }

    /**
     * Validates and sort registered login providers
     */
    protected function getValidatedAndSortedProviders(): array
    {
        $providers = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] ?? [];
        if (!is_array($providers)) {
            throw new \RuntimeException('$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'backend\'][\'loginProviders\'] must be an array', 1724699173);
        }
        if (empty($providers)) {
            throw new \RuntimeException('No login providers are registered in $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'backend\'][\'loginProviders\'].', 1433417281);
        }
        foreach ($providers as $identifier => $configuration) {
            if (empty($configuration) || !is_array($configuration)) {
                throw new \RuntimeException('Missing configuration for login provider "' . $identifier . '".', 1433416043);
            }
            if (!is_string($configuration['provider']) || empty($configuration['provider']) || !class_exists($configuration['provider']) || !is_subclass_of($configuration['provider'], LoginProviderInterface::class)) {
                throw new \RuntimeException('The login provider "' . $identifier . '" defines an invalid provider. Ensure the class exists and implements the "' . LoginProviderInterface::class . '".', 1460977275);
            }
            if (empty($configuration['label'])) {
                throw new \RuntimeException('Missing label definition for login provider "' . $identifier . '".', 1433416044);
            }
            if (empty($configuration['iconIdentifier'])) {
                throw new \RuntimeException('Missing icon definition for login provider "' . $identifier . '".', 1433416045);
            }
            if (!isset($configuration['sorting'])) {
                throw new \RuntimeException('Missing sorting definition for login provider "' . $identifier . '".', 1433416046);
            }
        }
        uasort($providers, static function (array $a, array $b): int {
            return (int)$b['sorting'] - (int)$a['sorting'];
        });
        return $providers;
    }
}
