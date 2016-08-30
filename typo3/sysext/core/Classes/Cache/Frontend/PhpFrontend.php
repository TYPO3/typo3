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
 *
 * This file is a backport from FLOW3
 * @api
 */
class PhpFrontend extends StringFrontend
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
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
     * @return void
     * @throws \InvalidArgumentException If $entryIdentifier or $tags is invalid
     * @throws InvalidDataException If $sourceCode is not a string
     * @api
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
     * Loads PHP code from the cache and require_onces it right away.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed Potential return value from the include operation
     * @api
     */
    public function requireOnce($entryIdentifier)
    {
        return $this->backend->requireOnce($entryIdentifier);
    }
}
