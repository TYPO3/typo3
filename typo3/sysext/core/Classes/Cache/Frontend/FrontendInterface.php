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

namespace TYPO3\CMS\Core\Cache\Frontend;

/**
 * Contract for a Cache (frontend)
 */
interface FrontendInterface
{
    /**
     * "Magic" tag for class-related entries
     */
    const TAG_CLASS = '%CLASS%';
    /**
     * "Magic" tag for package-related entries
     */
    const TAG_PACKAGE = '%PACKAGE%';
    /**
     * Pattern an entry identifier must match.
     */
    const PATTERN_ENTRYIDENTIFIER = '/^[a-zA-Z0-9_%\\-&]{1,250}$/';
    /**
     * Pattern a tag must match.
     */
    const PATTERN_TAG = '/^[a-zA-Z0-9_%\\-&]{1,250}$/';
    /**
     * Returns this cache's identifier
     *
     * @return string The identifier for this cache
     */
    public function getIdentifier();

    /**
     * Returns the backend used by this cache
     *
     * @return \TYPO3\CMS\Core\Cache\Backend\BackendInterface The backend used by this cache
     */
    public function getBackend();

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier Something which identifies the data - depends on concrete cache
     * @param mixed $data The data to cache - also depends on the concrete cache implementation
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null);

    /**
     * Finds and returns data from the cache.
     *
     * @param string $entryIdentifier Something which identifies the cache entry - depends on concrete cache
     * @return mixed
     */
    public function get($entryIdentifier);

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return bool TRUE if such an entry exists, FALSE if not
     */
    public function has($entryIdentifier);

    /**
     * Removes the given cache entry from the cache.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return bool TRUE if such an entry exists, FALSE if not
     */
    public function remove($entryIdentifier);

    /**
     * Removes all cache entries of this cache.
     */
    public function flush();

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     */
    public function flushByTag($tag);

    /**
     * Removes all cache entries of this cache which are tagged by any of the specified tags.
     *
     * @param string[] $tags List of tags
     */
    public function flushByTags(array $tags);

    /**
     * Does garbage collection
     */
    public function collectGarbage();

    /**
     * Checks the validity of an entry identifier. Returns TRUE if it's valid.
     *
     * @param string $identifier An identifier to be checked for validity
     * @return bool
     */
    public function isValidEntryIdentifier($identifier);

    /**
     * Checks the validity of a tag. Returns TRUE if it's valid.
     *
     * @param string $tag A tag to be checked for validity
     * @return bool
     */
    public function isValidTag($tag);
}
