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
 * A caching backend which stores cache entries during one script run.
 *
 * This file is a backport from FLOW3
 * @api
 */
class TransientMemoryBackend extends \TYPO3\CMS\Core\Cache\Backend\AbstractBackend implements TaggableBackendInterface, TransientBackendInterface
{
    /**
     * @var array
     */
    protected $entries = [];

    /**
     * @var array
     */
    protected $tagsAndEntries = [];

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws \TYPO3\CMS\Core\Cache\Exception if no cache frontend has been set.
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if (!$this->cache instanceof \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface) {
            throw new \TYPO3\CMS\Core\Cache\Exception('No cache frontend has been set yet via setCache().', 1238244992);
        }
        $this->entries[$entryIdentifier] = $data;
        foreach ($tags as $tag) {
            $this->tagsAndEntries[$tag][$entryIdentifier] = true;
        }
    }

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     * @api
     */
    public function get($entryIdentifier)
    {
        return isset($this->entries[$entryIdentifier]) ? $this->entries[$entryIdentifier] : false;
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return bool TRUE if such an entry exists, FALSE if not
     * @api
     */
    public function has($entryIdentifier)
    {
        return isset($this->entries[$entryIdentifier]);
    }

    /**
     * Removes all cache entries matching the specified identifier.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return bool TRUE if the entry could be removed or FALSE if no entry was found
     * @api
     */
    public function remove($entryIdentifier)
    {
        if (isset($this->entries[$entryIdentifier])) {
            unset($this->entries[$entryIdentifier]);
            foreach ($this->tagsAndEntries as $tag => $_) {
                if (isset($this->tagsAndEntries[$tag][$entryIdentifier])) {
                    unset($this->tagsAndEntries[$tag][$entryIdentifier]);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     * @api
     */
    public function findIdentifiersByTag($tag)
    {
        if (isset($this->tagsAndEntries[$tag])) {
            return array_keys($this->tagsAndEntries[$tag]);
        }
        return [];
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @api
     */
    public function flush()
    {
        $this->entries = [];
        $this->tagsAndEntries = [];
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @api
     */
    public function flushByTag($tag)
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
    }

    /**
     * Does nothing
     *
     * @api
     */
    public function collectGarbage()
    {
    }
}
