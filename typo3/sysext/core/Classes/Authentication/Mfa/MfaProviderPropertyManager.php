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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Basic manager for MFA providers to access and update their
 * properties (information) from the mfa column in the user array.
 */
class MfaProviderPropertyManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected const DATABASE_FIELD_NAME = 'mfa';

    protected array $mfa;
    protected array $providerProperties;

    public function __construct(protected readonly AbstractUserAuthentication $user, protected readonly string $providerIdentifier)
    {
        $this->mfa = json_decode($user->user[self::DATABASE_FIELD_NAME] ?? '', true) ?? [];
        $this->providerProperties = $this->mfa[$this->providerIdentifier] ?? [];
    }

    /**
     * Check if a provider entry exists for the current user
     */
    public function hasProviderEntry(): bool
    {
        return isset($this->mfa[$this->providerIdentifier]);
    }

    /**
     * Check if a provider property exists
     */
    public function hasProperty(string $key): bool
    {
        return isset($this->providerProperties[$key]);
    }

    /**
     * Get a provider specific property value or the defined
     * default value if the requested property was not found.
     */
    public function getProperty(string $key, mixed $default = null): mixed
    {
        return $this->providerProperties[$key] ?? $default;
    }

    /**
     * Get provider specific properties
     */
    public function getProperties(): array
    {
        return $this->providerProperties;
    }

    /**
     * Update the provider properties
     * Note: If no entry exists yet, use createProviderEntry() instead.
     *       This can be checked with hasProviderEntry().
     */
    public function updateProperties(array $properties): bool
    {
        // This is to prevent provider data inconsistency
        if (!$this->hasProviderEntry()) {
            throw new \InvalidArgumentException(
                'No entry for provider ' . $this->providerIdentifier . ' exists yet. Use createProviderEntry() instead.',
                1613993188
            );
        }

        if (!isset($properties['updated'])) {
            $properties['updated'] = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
        }

        $this->providerProperties = array_replace($this->providerProperties, $properties);
        $this->mfa[$this->providerIdentifier] = $this->providerProperties;
        return $this->storeProperties();
    }

    /**
     * Create a new provider entry for the current user
     * Note: If an entry already exists, use updateProperties() instead.
     *       This can be checked with hasProviderEntry().
     */
    public function createProviderEntry(array $properties): bool
    {
        // This is to prevent unintentional overwriting of provider entries
        if ($this->hasProviderEntry()) {
            throw new \InvalidArgumentException(
                'A entry for provider ' . $this->providerIdentifier . ' already exists. Use updateProperties() instead.',
                1612781782
            );
        }

        if (!isset($properties['created'])) {
            $properties['created'] = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
        }

        if (!isset($properties['updated'])) {
            $properties['updated'] = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
        }

        $this->providerProperties = $properties;
        $this->mfa[$this->providerIdentifier] = $this->providerProperties;
        return $this->storeProperties();
    }

    /**
     * Delete a provider entry for the current user
     *
     * @throws \JsonException
     */
    public function deleteProviderEntry(): bool
    {
        $this->providerProperties = [];
        unset($this->mfa[$this->providerIdentifier]);
        return $this->storeProperties();
    }

    /**
     * Stores the updated properties in the user array and the database
     *
     * @throws \JsonException
     */
    protected function storeProperties(): bool
    {
        // encode the mfa properties to store them in the database and the user array
        $mfa = json_encode($this->mfa, JSON_THROW_ON_ERROR) ?: '';

        // Write back the updated mfa properties to the user array
        $this->user->user[self::DATABASE_FIELD_NAME] = $mfa;

        // Log MFA update
        $this->logger->debug('MFA properties updated', [
            'provider' => $this->providerIdentifier,
            'user' => [
                'uid' => $this->user->getUserId(),
                'username' => $this->user->getUserName(),
            ],
        ]);

        // Store updated mfa properties in the database
        return (bool)GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->user->user_table)->update(
            $this->user->user_table,
            [self::DATABASE_FIELD_NAME => $mfa],
            [$this->user->userid_column => (int)$this->user->getUserId()],
            [self::DATABASE_FIELD_NAME => Connection::PARAM_LOB]
        );
    }

    /**
     * Return the current user
     */
    public function getUser(): AbstractUserAuthentication
    {
        return $this->user;
    }

    /**
     * Return the current providers identifier
     */
    public function getIdentifier(): string
    {
        return $this->providerIdentifier;
    }

    /**
     * Create property manager for the user with the given provider
     */
    public static function create(MfaProviderManifestInterface $provider, AbstractUserAuthentication $user): self
    {
        return GeneralUtility::makeInstance(self::class, $user, $provider->getIdentifier());
    }
}
