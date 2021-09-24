<?php

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

namespace TYPO3\CMS\Core\Package\Cache;

use TYPO3\CMS\Core\Package\Exception\PackageManagerCacheUnavailableException;
use TYPO3\CMS\Core\Package\Exception\PackageStatesUnavailableException;
use TYPO3\CMS\Core\Package\MetaData;
use TYPO3\CMS\Core\Package\MetaData\PackageConstraint;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageInterface;

/**
 * A TYPO3 Package cache entry.
 * Represents a concrete state of TYPO3 packages.
 * It interfaces between PackageManager and PackageCacheInterface.
 *
 * @internal
 */
class PackageCacheEntry
{
    /**
     * Package configuration. Used by the PackageManager to identify "active" packages.
     * Every key in this array represents an active extension.
     */
    private array $configuration;

    /**
     * Alternative names for packages mapping to the package key.
     * Typically filled from replace section in composer.json
     */
    private array $aliasMap;

    /**
     * Map from composer name of a package (key in this array) to its package key (value)
     */
    private array $composerNameMap;

    /**
     * @var PackageInterface[]
     */
    private array $packages;

    /**
     * Identifier for the current state, which can optionally
     * stored in the cache entry or artifact.
     * Currently, only used in Composer mode, where the identifier
     * is comprised from the composer.lock file and stored alongside the artifact.
     *
     * @var string|null
     */
    private ?string $identifier = null;

    private function __construct(
        array $configuration,
        array $aliasMap,
        array $composerNameMap,
        array $packages
    ) {
        $this->configuration = $configuration;
        $this->aliasMap = $aliasMap;
        $this->composerNameMap = $composerNameMap;
        $this->packages = $packages;
    }

    /**
     * Validates whether the configuration has the correct version
     *
     * @param array $configuration
     * @throws PackageStatesUnavailableException
     */
    public static function ensureValidPackageConfiguration(array $configuration): void
    {
        if (($configuration['version'] ?? 0) < 5) {
            throw new PackageStatesUnavailableException('The PackageStates.php file is either corrupt or unavailable.', 1381507733);
        }
    }

    public static function fromPackageData(
        array $packageStatesConfiguration,
        array $packageAliasMap,
        array $composerNameToPackageKeyMap,
        array $packageObjects
    ): self {
        self::ensureValidPackageConfiguration($packageStatesConfiguration);

        return new self(
            $packageStatesConfiguration,
            $packageAliasMap,
            $composerNameToPackageKeyMap,
            $packageObjects
        );
    }

    public static function fromCache(array $packageData): self
    {
        try {
            self::ensureValidPackageConfiguration($packageData['packageStatesConfiguration'] ?? []);
        } catch (PackageStatesUnavailableException $e) {
            // Invalidate the cache entry
            throw new PackageManagerCacheUnavailableException('The package state cache could not be loaded.', 1393883341, $e);
        }
        $cacheEntry = new self(
            $packageData['packageStatesConfiguration'],
            $packageData['packageAliasMap'],
            $packageData['composerNameToPackageKeyMap'],
            unserialize($packageData['packageObjects'], [
                'allowed_classes' => [
                    Package::class,
                    MetaData::class,
                    PackageConstraint::class,
                    \stdClass::class,
                ],
            ])
        );
        $cacheEntry->identifier = $packageData['identifier'] ?? null;

        return $cacheEntry;
    }

    public function serialize(): string
    {
        return var_export(
            [
                'identifier' => $this->identifier,
                'packageStatesConfiguration' => $this->configuration,
                'packageAliasMap' => $this->aliasMap,
                'composerNameToPackageKeyMap' => $this->composerNameMap,
                'packageObjects' => serialize($this->packages),
            ],
            true
        );
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function withIdentifier(string $identifier): self
    {
        $newEntry = clone $this;
        $newEntry->identifier = $identifier;

        return $newEntry;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getAliasMap(): array
    {
        return $this->aliasMap;
    }

    public function getComposerNameMap(): array
    {
        return $this->composerNameMap;
    }

    /**
     * @return PackageInterface[]
     */
    public function getPackages(): array
    {
        return $this->packages;
    }
}
