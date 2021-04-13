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
class LoginProviderResolver
{
    protected array $loginProviders = [];

    public function __construct(array $loginProviders = null)
    {
        if ($loginProviders === null) {
            $loginProviders = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] ?? [];
            if (!is_array($loginProviders)) {
                $loginProviders = [];
            }
        }

        $this->loginProviders = $this->validateAndSortLoginProviders($loginProviders);
    }

    /**
     * Validates the registered login providers
     *
     * @param array $providers
     * @return array the sorted login providers
     * @throws \RuntimeException
     */
    protected function validateAndSortLoginProviders(array $providers): array
    {
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
            if (empty($configuration['icon-class'])) {
                throw new \RuntimeException('Missing icon definition for login provider "' . $identifier . '".', 1433416045);
            }
            if (!isset($configuration['sorting'])) {
                throw new \RuntimeException('Missing sorting definition for login provider "' . $identifier . '".', 1433416046);
            }
        }
        // sort providers
        uasort($providers, static function ($a, $b) {
            return $b['sorting'] - $a['sorting'];
        });
        return $providers;
    }

    /**
     * Get all registered login providers in the right order
     * @return array
     */
    public function getLoginProviders(): array
    {
        return $this->loginProviders;
    }

    /**
     * Check if the login provider is registered.
     *
     * @param string $identifier
     * @return bool
     * @internal
     */
    public function hasLoginProvider(string $identifier): bool
    {
        return isset($this->loginProviders[$identifier]);
    }

    public function getLoginProviderConfigurationByIdentifier(string $identifier): array
    {
        return $this->loginProviders[$identifier] ?? [];
    }

    /**
     * Returns the identifier of the first login provider / with the highest priority.
     * @return string
     */
    public function getPrimaryLoginProviderIdentifier(): string
    {
        reset($this->loginProviders);
        return (string)key($this->loginProviders);
    }

    /**
     * Fetches the login provider identifier from a ServerRequest, checking for POST Body, then Query Param,
     * and then checks for the cookie.
     *
     * @param ServerRequestInterface $request
     * @param string $cookieName
     * @return string
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
}
