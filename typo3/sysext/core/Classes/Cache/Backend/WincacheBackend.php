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

use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * A caching backend which stores cache entries by using wincache.
 *
 * This backend uses the following types of keys:
 * - tag_xxx
 * xxx is tag name, value is array of associated identifiers identifier. This
 * is "forward" tag index. It is mainly used for obtaining content by tag
 * (get identifier by tag -> get content by identifier)
 * - ident_xxx
 * xxx is identifier, value is array of associated tags. This is "reverse" tag
 * index. It provides quick access for all tags associated with this identifier
 * and used when removing the identifier
 *
 * Each key is prepended with a prefix. By default prefix consists from two parts
 * separated by underscore character and ends in yet another underscore character:
 * - "TYPO3"
 * - MD5 of script path and filename and SAPI name
 * This prefix makes sure that keys from the different installations do not
 * conflict.
 */
class WincacheBackend extends AbstractBackend implements TaggableBackendInterface
{
    /**
     * A prefix to separate stored data from other data possible stored in the wincache
     *
     * @var string
     */
    protected $identifierPrefix;

    /**
     * Constructs this backend
     *
     * @param string $context Unused, for backward compatibility only
     * @param array $options Configuration options
     * @throws Exception If wincache PHP extension is not loaded
     */
    public function __construct($context, array $options = [])
    {
        if (!extension_loaded('wincache')) {
            throw new Exception('The PHP extension "wincache" must be installed and loaded in order to use the wincache backend.', 1343331520);
        }
        parent::__construct($context, $options);
    }

    /**
     * Saves data in the cache
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws Exception if no cache frontend has been set
     * @throws \InvalidArgumentException if the identifier is not valid
     * @throws InvalidDataException if $data is not a string
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('No cache frontend has been set yet via setCache().', 1343331521);
        }
        if (!is_string($data)) {
            throw new InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1343331522);
        }
        $tags[] = '%WCBE%' . $this->cache->getIdentifier();
        $expiration = $lifetime ?? $this->defaultLifetime;
        $success = wincache_ucache_set($this->identifierPrefix . $entryIdentifier, $data, $expiration);
        if ($success === true) {
            $this->removeIdentifierFromAllTags($entryIdentifier);
            $this->addIdentifierToTags($entryIdentifier, $tags);
        } else {
            throw new Exception('Could not set value.', 1343331523);
        }
    }

    /**
     * Loads data from the cache
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     */
    public function get($entryIdentifier)
    {
        $success = false;
        $value = wincache_ucache_get($this->identifierPrefix . $entryIdentifier, $success);
        return $success ? $value : $success;
    }

    /**
     * Checks if a cache entry with the specified identifier exists
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return bool TRUE if such an entry exists, FALSE if not
     */
    public function has($entryIdentifier)
    {
        return wincache_ucache_exists($this->identifierPrefix . $entryIdentifier);
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     */
    public function remove($entryIdentifier)
    {
        $this->removeIdentifierFromAllTags($entryIdentifier);
        return wincache_ucache_delete($this->identifierPrefix . $entryIdentifier);
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     */
    public function findIdentifiersByTag($tag)
    {
        $success = false;
        $identifiers = wincache_ucache_get($this->identifierPrefix . 'tag_' . $tag, $success);
        if ($success === false) {
            return [];
        }
        return (array)$identifiers;
    }

    /**
     * Finds all tags for the given identifier. This function uses reverse tag
     * index to search for tags.
     *
     * @param string $identifier Identifier to find tags by
     * @return array Array with tags
     */
    protected function findTagsByIdentifier($identifier)
    {
        $success = false;
        $tags = wincache_ucache_get($this->identifierPrefix . 'ident_' . $identifier, $success);
        return $success ? (array)$tags : [];
    }

    /**
     * Removes all cache entries of this cache
     *
     * @throws Exception
     */
    public function flush()
    {
        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('Yet no cache frontend has been set via setCache().', 1343331524);
        }
        $this->flushByTag('%WCBE%' . $this->cache->getIdentifier());
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified
     * tag.
     *
     * @param string $tag The tag the entries must have
     */
    public function flushByTag($tag)
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
    }

    /**
     * Associates the identifier with the given tags
     *
     * @param string $entryIdentifier
     * @param array $tags
     */
    protected function addIdentifierToTags($entryIdentifier, array $tags)
    {
        // Get identifier-to-tag index to look for updates
        $existingTags = $this->findTagsByIdentifier($entryIdentifier);
        $existingTagsUpdated = false;

        foreach ($tags as $tag) {
            // Update tag-to-identifier index
            $identifiers = $this->findIdentifiersByTag($tag);
            if (!in_array($entryIdentifier, $identifiers, true)) {
                $identifiers[] = $entryIdentifier;
                wincache_ucache_set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
            }
            // Test if identifier-to-tag index needs update
            if (!in_array($tag, $existingTags, true)) {
                $existingTags[] = $tag;
                $existingTagsUpdated = true;
            }
        }

        // Update identifier-to-tag index if needed
        if ($existingTagsUpdated) {
            wincache_ucache_set($this->identifierPrefix . 'ident_' . $entryIdentifier, $existingTags);
        }
    }

    /**
     * Removes association of the identifier with the given tags
     *
     * @param string $entryIdentifier
     */
    protected function removeIdentifierFromAllTags($entryIdentifier)
    {
        // Get tags for this identifier
        $tags = $this->findTagsByIdentifier($entryIdentifier);
        // Deassociate tags with this identifier
        foreach ($tags as $tag) {
            $identifiers = $this->findIdentifiersByTag($tag);
            // Formally array_search() below should never return false due to
            // the behavior of findTagsByIdentifier(). But if reverse index is
            // corrupted, we still can get 'false' from array_search(). This is
            // not a problem because we are removing this identifier from
            // anywhere.
            if (($key = array_search($entryIdentifier, $identifiers)) !== false) {
                unset($identifiers[$key]);
                if (!empty($identifiers)) {
                    wincache_ucache_set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
                } else {
                    wincache_ucache_delete($this->identifierPrefix . 'tag_' . $tag);
                }
            }
        }
        // Clear reverse tag index for this identifier
        wincache_ucache_delete($this->identifierPrefix . 'ident_' . $entryIdentifier);
    }

    /**
     * Does nothing, as wincache does GC itself
     */
    public function collectGarbage()
    {
    }
}
