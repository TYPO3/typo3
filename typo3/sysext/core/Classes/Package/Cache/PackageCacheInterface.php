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

/**
 * Interface for TYPO3 Package cache.
 *
 * This is an implementation detail to abstract the way the PackageManager
 * stores and retrieves package information of installed TYPO3 packages (extensions).
 *
 * In non Composer mode, the implementation remains to be around the availability
 * of a PackageStates.php file, which is used to generate a cache entry of Package objects.
 * in Composer mode, the package information is put in a persistent artifact file.
 *
 * @internal
 */
interface PackageCacheInterface
{
    /**
     * Fetch the (package states) entry from a persistent or transient location
     */
    public function fetch(): PackageCacheEntry;

    /**
     * Store the entry
     */
    public function store(PackageCacheEntry $cacheEntry): void;

    /**
     * Invalidate the current entry (only applicable in non Composer mode)
     */
    public function invalidate(): void;

    /**
     * Identifier that identifies the current state (typically a hash)
     */
    public function getIdentifier(): string;
}
