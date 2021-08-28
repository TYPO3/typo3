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

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\Exception\PackageManagerCacheUnavailableException;

/**
 * TYPO3 Package cache for package states file.
 * This replicates previous behaviour around the availability
 * of a PackageStates.php file and has been extracted from PackageManager
 *
 * @internal
 */
class PackageStatesPackageCache implements PackageCacheInterface
{
    private const CACHE_IDENTIFIER_PREFIX = 'PackageManager_';
    private ?string $cacheIdentifier;
    private string $packageStatesFile;
    private FrontendInterface $coreCache;

    public function __construct(string $packageStatesFile, FrontendInterface $coreCache)
    {
        $this->packageStatesFile = $packageStatesFile;
        $this->coreCache = $coreCache;
    }

    public function fetch(): PackageCacheEntry
    {
        $packageData = $this->coreCache->require(self::CACHE_IDENTIFIER_PREFIX . $this->getIdentifier());

        return PackageCacheEntry::fromCache($packageData ?: []);
    }

    public function store(PackageCacheEntry $cacheEntry): void
    {
        $cacheIdentifier = $this->getIdentifier();
        $this->coreCache->set(
            self::CACHE_IDENTIFIER_PREFIX . $cacheIdentifier,
            'return ' . PHP_EOL . $cacheEntry->withIdentifier($cacheIdentifier)->serialize() . ';'
        );
    }

    public function invalidate(): void
    {
        if (!isset($this->cacheIdentifier)) {
            return;
        }
        $this->coreCache->remove($this->cacheIdentifier);
        $this->cacheIdentifier = null;
    }

    /**
     * "Hash" the package states file when cacheIdentifier is null
     * This is done to cache the state and to represent invalidated state.
     *
     * @throws PackageManagerCacheUnavailableException
     */
    public function getIdentifier(): string
    {
        if (!isset($this->cacheIdentifier)) {
            $mTime = @filemtime($this->packageStatesFile);
            if ($mTime === false) {
                throw new PackageManagerCacheUnavailableException('The package state cache could not be loaded.', 1629817141);
            }
            $this->cacheIdentifier = md5((string)(new Typo3Version()) . $this->packageStatesFile . $mTime);
        }

        return $this->cacheIdentifier;
    }
}
