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

use TYPO3\CMS\Core\Cache\Backend\BackendInterface;
use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An abstract cache
 */
abstract class AbstractFrontend implements FrontendInterface
{
    /**
     * Identifies this cache
     *
     * @var string
     */
    protected $identifier;

    /**
     * @var BackendInterface|TaggableBackendInterface
     */
    protected $backend;

    /**
     * Constructs the cache
     *
     * @param string $identifier An identifier which describes this cache
     * @param BackendInterface $backend Backend to be used for this cache
     * @throws \InvalidArgumentException if the identifier doesn't match PATTERN_ENTRYIDENTIFIER
     */
    public function __construct($identifier, BackendInterface $backend)
    {
        if (preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) !== 1) {
            throw new \InvalidArgumentException('"' . $identifier . '" is not a valid cache identifier.', 1203584729);
        }
        if (strpos($identifier, 'cache_') === 0) {
            trigger_error('Setting up a cache as in "' . $identifier . '" with the "cache_" prefix is not necessary anymore, and should be called without the cache prefix.', E_USER_DEPRECATED);
            $identifier = substr($identifier, 6);
        }
        $this->identifier = $identifier;
        $this->backend = $backend;
        $this->backend->setCache($this);
    }

    /**
     * Returns this cache's identifier
     *
     * @return string The identifier for this cache
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns the backend used by this cache
     *
     * @return BackendInterface The backend used by this cache
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return bool TRUE if such an entry exists, FALSE if not
     * @throws \InvalidArgumentException If $entryIdentifier is invalid
     */
    public function has($entryIdentifier)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058486);
        }
        return $this->backend->has($entryIdentifier);
    }

    /**
     * Removes the given cache entry from the cache.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return bool TRUE if such an entry exists, FALSE if not
     * @throws \InvalidArgumentException
     */
    public function remove($entryIdentifier)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058495);
        }
        return $this->backend->remove($entryIdentifier);
    }

    /**
     * Removes all cache entries of this cache.
     */
    public function flush()
    {
        $this->backend->flush();
    }

    /**
     * Removes all cache entries of this cache which are tagged by any of the specified tags.
     *
     * @param string[] $tags
     * @throws \InvalidArgumentException
     */
    public function flushByTags(array $tags)
    {
        foreach ($tags as $tag) {
            if (!$this->isValidTag($tag)) {
                throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057360);
            }
        }
        if ($this->backend instanceof TaggableBackendInterface) {
            $this->backend->flushByTags($tags);
        }
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @throws \InvalidArgumentException
     */
    public function flushByTag($tag)
    {
        if (!$this->isValidTag($tag)) {
            throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057359);
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/cache/frontend/class.t3lib_cache_frontend_abstractfrontend.php']['flushByTag'] ?? [] as $_funcRef) {
            $params = ['tag' => $tag];
            GeneralUtility::callUserFunction($_funcRef, $params, $this);
        }

        if ($this->backend instanceof TaggableBackendInterface) {
            $this->backend->flushByTag($tag);
        }
    }

    /**
     * Does garbage collection
     */
    public function collectGarbage()
    {
        $this->backend->collectGarbage();
    }

    /**
     * Checks the validity of an entry identifier. Returns TRUE if it's valid.
     *
     * @param string $identifier An identifier to be checked for validity
     * @return bool
     */
    public function isValidEntryIdentifier($identifier)
    {
        return preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) === 1;
    }

    /**
     * Checks the validity of a tag. Returns TRUE if it's valid.
     *
     * @param string|array $tag An identifier to be checked for validity
     * @return bool
     */
    public function isValidTag($tag)
    {
        if (!is_array($tag)) {
            return preg_match(self::PATTERN_TAG, $tag) === 1;
        }
        foreach ($tag as $tagValue) {
            if (!$this->isValidTag($tagValue)) {
                return false;
            }
        }
        return true;
    }
}
