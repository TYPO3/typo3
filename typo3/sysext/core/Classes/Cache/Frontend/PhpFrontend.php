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

use TYPO3\CMS\Core\Cache\Backend\PhpCapableBackendInterface;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;

/**
 * A cache frontend tailored to PHP code.
 */
class PhpFrontend extends AbstractFrontend
{
    /**
     * Constructs the cache
     *
     * @param string $identifier An identifier which describes this cache
     * @param PhpCapableBackendInterface $backend Backend to be used for this cache
     */
    public function __construct($identifier, PhpCapableBackendInterface $backend)
    {
        parent::__construct($identifier, $backend);
    }

    /**
     * Saves the PHP source code in the cache.
     *
     * @param string $entryIdentifier An identifier used for this cache entry, for example the class name
     * @param string $sourceCode PHP source code
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws \InvalidArgumentException If $entryIdentifier or $tags is invalid
     * @throws InvalidDataException If $sourceCode is not a string
     */
    public function set($entryIdentifier, $sourceCode, array $tags = [], $lifetime = null)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1264023823);
        }
        if (!is_string($sourceCode)) {
            throw new InvalidDataException('The given source code is not a valid string.', 1264023824);
        }
        foreach ($tags as $tag) {
            if (!$this->isValidTag($tag)) {
                throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1264023825);
            }
        }
        $sourceCode = '<?php' . LF . $sourceCode . LF . '#';
        $this->backend->set($entryIdentifier, $sourceCode, $tags, $lifetime);
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
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057753);
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
        trigger_error('PhpFrontend->getByTag() will be removed in TYPO3 v10.0. Avoid using this method since it is not compliant to PSR-6.', E_USER_DEPRECATED);
        if (!$this->isValidTag($tag)) {
            throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057773);
        }
        $entries = [];
        $identifiers = $this->backend->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $entries[] = $this->backend->get($identifier);
        }
        return $entries;
    }

    /**
     * Loads PHP code from the cache and require_onces it right away.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed Potential return value from the include operation
     */
    public function requireOnce($entryIdentifier)
    {
        return $this->backend->requireOnce($entryIdentifier);
    }

    /**
     * Loads PHP code from the cache and require() it right away. Note require()
     * in comparison to requireOnce() is only "safe" if the cache entry only contain stuff
     * that can be required multiple times during one request. For instance a class definition
     * would fail here.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed Potential return value from the include operation
     */
    public function require(string $entryIdentifier)
    {
        return $this->backend->require($entryIdentifier);
    }
}
