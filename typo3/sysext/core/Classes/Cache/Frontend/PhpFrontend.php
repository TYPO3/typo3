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

namespace TYPO3\CMS\Core\Cache\Frontend;

use TYPO3\CMS\Core\Cache\Backend\PhpCapableBackendInterface;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;

/**
 * A cache frontend tailored to PHP code.
 */
class PhpFrontend extends AbstractFrontend
{
    public function __construct(string $identifier, PhpCapableBackendInterface $backend)
    {
        parent::__construct($identifier, $backend);
    }

    public function set(string $entryIdentifier, mixed $data, array $tags = [], ?int $lifetime = null): void
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1264023823);
        }
        if (!is_string($data)) {
            throw new InvalidDataException('The given source code is not a valid string.', 1264023824);
        }
        foreach ($tags as $tag) {
            if (!$this->isValidTag($tag)) {
                throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1264023825);
            }
        }
        $sourceCode = '<?php' . LF . $data . LF . '#';
        $this->backend->set($entryIdentifier, $sourceCode, $tags, $lifetime);
    }

    public function get(string $entryIdentifier): mixed
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057753);
        }
        return $this->backend->get($entryIdentifier);
    }

    /**
     * Loads PHP code from the cache and require_onces it right away.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed Potential return value from the include operation
     */
    public function requireOnce(string $entryIdentifier): mixed
    {
        $backend = $this->getBackend();
        if (!($backend instanceof PhpCapableBackendInterface)) {
            throw new \RuntimeException('Can not require: Not a PhpCapableBackendInterface', 1763660480);
        }
        return $backend->requireOnce($entryIdentifier);
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
    public function require(string $entryIdentifier): mixed
    {
        $backend = $this->getBackend();
        if (!($backend instanceof PhpCapableBackendInterface)) {
            throw new \RuntimeException('Can not require: Not a PhpCapableBackendInterface', 1763660481);
        }
        return $backend->require($entryIdentifier);
    }
}
