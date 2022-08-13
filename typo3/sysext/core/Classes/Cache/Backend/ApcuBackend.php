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

namespace TYPO3\CMS\Core\Cache\Backend;

use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;

/**
 * A caching backend which stores cache entries by using APCu.
 *
 * The APCu backend is not very good with tagging and scales O(2n) with the
 * number of tags. Do not use this backend if the data to be cached has many tags!
 *
 * This backend uses the following types of keys:
 * - tag_xxx
 *   xxx is tag name, value is array of associated identifiers identifier. This
 *   is "forward" tag index. It is mainly used for obtaining content by tag
 *   (get identifier by tag -> get content by identifier)
 * - ident_xxx
 *   xxx is identifier, value is array of associated tags. This is "reverse" tag
 *   index. It provides quick access for all tags associated with this identifier
 *   and used when removing the identifier
 *
 * Each key is prepended with a prefix. The prefix makes sure keys from the different
 * installations do not conflict. By default, prefix consists from two parts
 * separated by underscore character and ends in yet another underscore character:
 * - "TYPO3"
 * - Hash of path to TYPO3 and user running TYPO3
 */
final class ApcuBackend extends AbstractBackend implements TaggableBackendInterface, TransientBackendInterface
{
    /**
     * A prefix to separate stored data from other data possible stored in the APC.
     */
    private string $identifierPrefix = '';

    /**
     * Constructs this backend
     *
     * @param string $context Unused, for backward compatibility only
     * @param array $options Configuration options - unused here
     * @throws Exception
     */
    public function __construct($context, array $options = [])
    {
        if (!extension_loaded('apcu')) {
            throw new Exception('The PHP extension "apcu" must be installed and loaded in order to use the APCu backend.', 1232985914);
        }
        if (PHP_SAPI === 'cli' && ini_get('apc.enable_cli') == 0) {
            throw new Exception('The APCu backend cannot be used because apcu is disabled on CLI.', 1232985915);
        }
        parent::__construct($context, $options);
    }

    /**
     * Initializes the identifier prefix when setting the cache.
     */
    public function setCache(FrontendInterface $cache): void
    {
        parent::setCache($cache);
        $this->identifierPrefix = 'TYPO3_' . hash('xxh3', Environment::getProjectPath() . $this->context . $cache->getIdentifier()) . '_';
    }

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param mixed $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws Exception if no cache frontend has been set.
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null): void
    {
        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('No cache frontend has been set yet via setCache().', 1232986118);
        }
        $expiration = $lifetime ?? $this->defaultLifetime;
        $success = apcu_store($this->identifierPrefix . $entryIdentifier, $data, $expiration);
        if ($success === true) {
            $this->removeIdentifierFromAllTags($entryIdentifier);
            $this->addIdentifierToTags($entryIdentifier, $tags);
        } else {
            $this->logger->alert('Error using APCu: Could not save data in the cache.');
        }
    }

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     */
    public function get($entryIdentifier): mixed
    {
        $success = false;
        $value = apcu_fetch($this->identifierPrefix . $entryIdentifier, $success);
        return $success ? $value : $success;
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return bool TRUE if such an entry exists, FALSE if not
     */
    public function has($entryIdentifier): bool
    {
        $success = false;
        apcu_fetch($this->identifierPrefix . $entryIdentifier, $success);
        return $success;
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     */
    public function remove($entryIdentifier): bool
    {
        $this->removeIdentifierFromAllTags($entryIdentifier);
        return apcu_delete($this->identifierPrefix . $entryIdentifier);
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     */
    public function findIdentifiersByTag($tag): array
    {
        $success = false;
        $identifiers = apcu_fetch($this->identifierPrefix . 'tag_' . $tag, $success);
        if ($success === false) {
            return [];
        }
        return (array)$identifiers;
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @throws Exception
     */
    public function flush(): void
    {
        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('Yet no cache frontend has been set via setCache().', 1232986571);
        }
        apcu_delete(new \APCUIterator('/^' . preg_quote($this->identifierPrefix, '/') . '/'));
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     */
    public function flushByTag($tag): void
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
    }

    /**
     * Associates the identifier with the given tags
     */
    private function addIdentifierToTags(string $entryIdentifier, array $tags): void
    {
        // Get identifier-to-tag index to look for updates
        $existingTags = $this->findTagsByIdentifier($entryIdentifier);
        $existingTagsUpdated = false;

        foreach ($tags as $tag) {
            // Update tag-to-identifier index
            $identifiers = $this->findIdentifiersByTag($tag);
            if (!in_array($entryIdentifier, $identifiers, true)) {
                $identifiers[] = $entryIdentifier;
                apcu_store($this->identifierPrefix . 'tag_' . $tag, $identifiers);
            }
            // Test if identifier-to-tag index needs update
            if (!in_array($tag, $existingTags, true)) {
                $existingTags[] = $tag;
                $existingTagsUpdated = true;
            }
        }

        // Update identifier-to-tag index if needed
        if ($existingTagsUpdated) {
            apcu_store($this->identifierPrefix . 'ident_' . $entryIdentifier, $existingTags);
        }
    }

    /**
     * Removes association of the identifier with the given tags
     */
    private function removeIdentifierFromAllTags(string $entryIdentifier): void
    {
        // Get tags for this identifier
        $tags = $this->findTagsByIdentifier($entryIdentifier);
        // De-associate tags with this identifier
        foreach ($tags as $tag) {
            $identifiers = $this->findIdentifiersByTag($tag);
            // Formally array_search() below should never return FALSE due to
            // the behavior of findTagsByIdentifier(). But if reverse index is
            // corrupted, we still can get 'FALSE' from array_search(). This is
            // not a problem because we are removing this identifier from
            // anywhere.
            if (($key = array_search($entryIdentifier, $identifiers)) !== false) {
                unset($identifiers[$key]);
                if (!empty($identifiers)) {
                    apcu_store($this->identifierPrefix . 'tag_' . $tag, $identifiers);
                } else {
                    apcu_delete($this->identifierPrefix . 'tag_' . $tag);
                }
            }
        }
        // Clear reverse tag index for this identifier
        apcu_delete($this->identifierPrefix . 'ident_' . $entryIdentifier);
    }

    /**
     * Finds all tags for the given identifier. This function uses reverse tag
     * index to search for tags.
     */
    private function findTagsByIdentifier(string $identifier): array
    {
        $success = false;
        $tags = apcu_fetch($this->identifierPrefix . 'ident_' . $identifier, $success);
        return $success ? (array)$tags : [];
    }

    public function collectGarbage(): void
    {
        // Noop, APCu has internal GC
    }
}
