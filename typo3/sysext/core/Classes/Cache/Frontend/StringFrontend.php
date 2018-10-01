<?php
namespace TYPO3\CMS\Core\Cache\Frontend;

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

use TYPO3\CMS\Core\Cache\Backend\BackendInterface;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;

/**
 * A cache frontend for strings. Nothing else.
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - use VariableFrontend instead.
 */
class StringFrontend extends AbstractFrontend
{
    /**
     * @param string $identifier
     * @param BackendInterface $backend
     */
    public function __construct($identifier, BackendInterface $backend)
    {
        trigger_error(
            sprintf(
                'Usage of class %s will be removed in TYPO3 v10.0, use %s instead',
                static::class,
                VariableFrontend::class
            ),
            E_USER_DEPRECATED
        );
        parent::__construct($identifier, $backend);
    }

    /**
     * Saves the value of a PHP variable in the cache.
     *
     * @param string $entryIdentifier An identifier used for this cache entry
     * @param string $string The variable to cache
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws \InvalidArgumentException if the identifier or tag is not valid
     * @throws InvalidDataException if the variable to cache is not of type string
     */
    public function set($entryIdentifier, $string, array $tags = [], $lifetime = null)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057566);
        }
        if (!is_string($string)) {
            throw new InvalidDataException('Given data is of type "' . gettype($string) . '", but a string is expected for string cache.', 1222808333);
        }
        foreach ($tags as $tag) {
            if (!$this->isValidTag($tag)) {
                throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057512);
            }
        }
        $this->backend->set($entryIdentifier, $string, $tags, $lifetime);
    }

    /**
     * Finds and returns a variable value from the cache.
     *
     * @param string $entryIdentifier Identifier of the cache entry to fetch
     * @return string The value
     * @throws \InvalidArgumentException if the cache identifier is not valid
     */
    public function get($entryIdentifier)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057752);
        }
        return $this->backend->get($entryIdentifier);
    }

    /**
     * Finds and returns all cache entries which are tagged by the specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array with the content of all matching entries. An empty array if no entries matched
     * @throws \InvalidArgumentException if the tag is not valid
     * @deprecated since TYPO3 v9, Avoid using this method since it is not compliant to PSR-6
     */
    public function getByTag($tag)
    {
        trigger_error('StringFrontend->getByTag() will be removed in TYPO3 v10.0. Avoid using this method since it is not compliant to PSR-6.', E_USER_DEPRECATED);
        if (!$this->isValidTag($tag)) {
            throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057772);
        }
        $entries = [];
        $identifiers = $this->backend->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $entries[] = $this->backend->get($identifier);
        }
        return $entries;
    }
}
