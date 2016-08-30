<?php
namespace TYPO3\CMS\Core\Cache\Backend;

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

/**
 * A contract for a Cache Backend
 * @api
 */
interface BackendInterface
{
    /**
     * Sets a reference to the cache frontend which uses this backend
     *
     * @param \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache The frontend for this backend
     * @return void
     * @api
     */
    public function setCache(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache);

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry. If the backend does not support tags, this option can be ignored.
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return void
     * @throws \TYPO3\CMS\Core\Cache\Exception if no cache frontend has been set.
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if the data is not a string
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null);

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     * @api
     */
    public function get($entryIdentifier);

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return bool TRUE if such an entry exists, FALSE if not
     * @api
     */
    public function has($entryIdentifier);

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     * @api
     */
    public function remove($entryIdentifier);

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @api
     */
    public function flush();

    /**
     * Does garbage collection
     *
     * @return void
     * @api
     */
    public function collectGarbage();
}
