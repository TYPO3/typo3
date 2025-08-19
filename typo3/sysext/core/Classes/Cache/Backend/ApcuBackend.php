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
     * @param array $options Configuration options - unused here
     */
    public function __construct(array $options = [])
    {
        if (!extension_loaded('apcu')) {
            throw new Exception('The PHP extension "apcu" must be installed and loaded in order to use the APCu backend.', 1232985914);
        }
        if (PHP_SAPI === 'cli' && ini_get('apc.enable_cli') == 0) {
            throw new Exception('The APCu backend cannot be used because apcu is disabled on CLI.', 1232985915);
        }
        parent::__construct($options);
    }

    public function setCache(FrontendInterface $cache): void
    {
        parent::setCache($cache);
        $this->identifierPrefix = 'TYPO3_' . hash('xxh3', Environment::getProjectPath() . $cache->getIdentifier()) . '_';
    }

    /**
     * @param mixed $data The data to be stored. mixed is allowed due to TransientBackendInterface
     */
    public function set(string $entryIdentifier, mixed $data, array $tags = [], ?int $lifetime = null): void
    {
        $lifetime ??= $this->defaultLifetime;
        $success = apcu_store($this->identifierPrefix . $entryIdentifier, $data, $lifetime);
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
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     */
    public function get(string $entryIdentifier): mixed
    {
        $success = false;
        $value = apcu_fetch($this->identifierPrefix . $entryIdentifier, $success);
        return $success ? $value : $success;
    }

    public function has(string $entryIdentifier): bool
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
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     */
    public function remove(string $entryIdentifier): bool
    {
        $this->removeIdentifierFromAllTags($entryIdentifier);
        return apcu_delete($this->identifierPrefix . $entryIdentifier);
    }

    public function findIdentifiersByTag(string $tag): array
    {
        $success = false;
        $identifiers = apcu_fetch($this->identifierPrefix . 'tag_' . $tag, $success);
        if ($success === false) {
            return [];
        }
        return (array)$identifiers;
    }

    public function flush(): void
    {
        apcu_delete(new \APCUIterator('/^' . preg_quote($this->identifierPrefix, '/') . '/'));
    }

    public function flushByTag(string $tag): void
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
    }

    public function flushByTags(array $tags): void
    {
        array_walk($tags, $this->flushByTag(...));
    }

    public function collectGarbage(): void
    {
        // Noop, APCu has internal GC
    }

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

    private function findTagsByIdentifier(string $identifier): array
    {
        $success = false;
        $tags = apcu_fetch($this->identifierPrefix . 'ident_' . $identifier, $success);
        return $success ? (array)$tags : [];
    }
}
