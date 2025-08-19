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
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;

/**
 * A contract for a cache backends which store variables in volatile
 * memory and as such support receiving any variable type to store.
 *
 * Note: respect for this contract is up to each individual frontend.
 * The contract can be respected for a small performance boost, but
 * the result is marginal except for cases with huge serialized
 * data sets.
 *
 * Respected by the VariableFrontend which checks if the backend
 * has this interface, in which case it allows the backend to store
 * the value directly without serializing it to a string, and does
 * not attempt to unserialize the string on every get() request.
 */
interface TransientBackendInterface extends BackendInterface
{
    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param mixed $data The data to be stored
     * @param array $tags Tags to associate with this cache entry. If the backend does not support tags, this option can be ignored.
     * @param int|null $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws Exception if no cache frontend has been set.
     * @throws InvalidDataException if the data is not a string
     */
    public function set(string $entryIdentifier, mixed $data, array $tags = [], ?int $lifetime = null): void;
}
