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
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * A caching backend which stores cache entries in files
 */
class FileBackend extends SimpleFileBackend implements FreezableBackendInterface, TaggableBackendInterface
{
    const SEPARATOR = '^';
    const EXPIRYTIME_FORMAT = 'YmdHis';
    const EXPIRYTIME_LENGTH = 14;
    const DATASIZE_DIGITS = 10;
    /**
     * A file extension to use for each cache entry.
     *
     * @var string
     */
    protected $cacheEntryFileExtension = '';

    /**
     * @var array
     */
    protected $cacheEntryIdentifiers = [];

    /**
     * @var bool
     */
    protected $frozen = false;

    /**
     * Freezes this cache backend.
     *
     * All data in a frozen backend remains unchanged and methods which try to add
     * or modify data result in an exception thrown. Possible expiry times of
     * individual cache entries are ignored.
     *
     * On the positive side, a frozen cache backend is much faster on read access.
     * A frozen backend can only be thawed by calling the flush() method.
     *
     * @throws \RuntimeException
     */
    public function freeze()
    {
        if ($this->frozen === true) {
            throw new \RuntimeException(sprintf('The cache "%s" is already frozen.', $this->cacheIdentifier), 1323353176);
        }
        $cacheEntryFileExtensionLength = strlen($this->cacheEntryFileExtension);
        for ($directoryIterator = new \DirectoryIterator($this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
            if ($directoryIterator->isDot()) {
                continue;
            }
            if ($cacheEntryFileExtensionLength > 0) {
                $entryIdentifier = substr($directoryIterator->getFilename(), 0, -$cacheEntryFileExtensionLength);
            } else {
                $entryIdentifier = $directoryIterator->getFilename();
            }
            $this->cacheEntryIdentifiers[$entryIdentifier] = true;
            file_put_contents($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension, $this->get($entryIdentifier));
        }
        file_put_contents($this->cacheDirectory . 'FrozenCache.data', serialize($this->cacheEntryIdentifiers));
        $this->frozen = true;
    }

    /**
     * Tells if this backend is frozen.
     *
     * @return bool
     */
    public function isFrozen()
    {
        return $this->frozen;
    }

    /**
     * Sets a reference to the cache frontend which uses this backend and
     * initializes the default cache directory.
     *
     * This method also detects if this backend is frozen and sets the internal
     * flag accordingly.
     *
     * @param FrontendInterface $cache The cache frontend
     */
    public function setCache(FrontendInterface $cache)
    {
        parent::setCache($cache);
        if (file_exists($this->cacheDirectory . 'FrozenCache.data')) {
            $this->frozen = true;
            $this->cacheEntryIdentifiers = unserialize((string)file_get_contents($this->cacheDirectory . 'FrozenCache.data'));
        }
    }

    /**
     * Saves data in a cache file.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws \RuntimeException
     * @throws InvalidDataException if the directory does not exist or is not writable or exceeds the maximum allowed path length, or if no cache frontend has been set.
     * @throws Exception if the directory does not exist or is not writable or exceeds the maximum allowed path length, or if no cache frontend has been set.
     * @throws \InvalidArgumentException
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if (!is_string($data)) {
            throw new InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1204481674);
        }
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073032);
        }
        if ($entryIdentifier === '') {
            throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1298114280);
        }
        if ($this->frozen === true) {
            throw new \RuntimeException(sprintf('Cannot add or modify cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1323344192);
        }
        $this->remove($entryIdentifier);
        $temporaryCacheEntryPathAndFilename = $this->cacheDirectory . StringUtility::getUniqueId() . '.temp';
        $lifetime = (int)($lifetime ?? $this->defaultLifetime);
        $expiryTime = $lifetime === 0 ? 0 : (int)($GLOBALS['EXEC_TIME'] + $lifetime);
        $metaData = str_pad((string)$expiryTime, self::EXPIRYTIME_LENGTH) . implode(' ', $tags) . str_pad((string)strlen($data), self::DATASIZE_DIGITS);
        $result = file_put_contents($temporaryCacheEntryPathAndFilename, $data . $metaData);
        GeneralUtility::fixPermissions($temporaryCacheEntryPathAndFilename);
        if ($result === false) {
            throw new Exception('The temporary cache file "' . $temporaryCacheEntryPathAndFilename . '" could not be written.', 1204026251);
        }
        $i = 0;
        $cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        while (($result = rename($temporaryCacheEntryPathAndFilename, $cacheEntryPathAndFilename)) === false && $i < 5) {
            $i++;
        }
        if ($result === false) {
            throw new Exception('The cache file "' . $cacheEntryPathAndFilename . '" could not be written.', 1222361632);
        }
        if ($this->cacheEntryFileExtension === '.php') {
            GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive($cacheEntryPathAndFilename);
        }
    }

    /**
     * Loads data from a cache file.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     * @throws \InvalidArgumentException If identifier is invalid
     */
    public function get($entryIdentifier)
    {
        if ($this->frozen === true) {
            return isset($this->cacheEntryIdentifiers[$entryIdentifier]) ? file_get_contents($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension) : false;
        }
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073033);
        }
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        if ($this->isCacheFileExpired($pathAndFilename)) {
            return false;
        }
        $dataSize = (int)file_get_contents(
            $pathAndFilename,
            false,
            null,
            filesize($pathAndFilename) - self::DATASIZE_DIGITS,
            self::DATASIZE_DIGITS
        );
        return file_get_contents($pathAndFilename, false, null, 0, $dataSize);
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier
     * @return bool TRUE if such an entry exists, FALSE if not
     * @throws \InvalidArgumentException
     */
    public function has($entryIdentifier)
    {
        if ($this->frozen === true) {
            return isset($this->cacheEntryIdentifiers[$entryIdentifier]);
        }
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073034);
        }
        return !$this->isCacheFileExpired($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function remove($entryIdentifier)
    {
        if ($this->frozen === true) {
            throw new \RuntimeException(sprintf('Cannot remove cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1323344193);
        }
        return parent::remove($entryIdentifier);
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $searchedTag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     */
    public function findIdentifiersByTag($searchedTag)
    {
        $entryIdentifiers = [];
        $now = $GLOBALS['EXEC_TIME'];
        $cacheEntryFileExtensionLength = strlen($this->cacheEntryFileExtension);
        for ($directoryIterator = GeneralUtility::makeInstance(\DirectoryIterator::class, $this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
            if ($directoryIterator->isDot()) {
                continue;
            }
            $cacheEntryPathAndFilename = $directoryIterator->getPathname();
            $index = (int)file_get_contents(
                $cacheEntryPathAndFilename,
                false,
                null,
                filesize($cacheEntryPathAndFilename) - self::DATASIZE_DIGITS,
                self::DATASIZE_DIGITS
            );
            $metaData = (string)file_get_contents($cacheEntryPathAndFilename, false, null, $index);
            $expiryTime = (int)substr($metaData, 0, self::EXPIRYTIME_LENGTH);
            if ($expiryTime !== 0 && $expiryTime < $now) {
                continue;
            }
            if (in_array($searchedTag, explode(' ', substr($metaData, self::EXPIRYTIME_LENGTH, -self::DATASIZE_DIGITS)))) {
                if ($cacheEntryFileExtensionLength > 0) {
                    $entryIdentifiers[] = substr($directoryIterator->getFilename(), 0, -$cacheEntryFileExtensionLength);
                } else {
                    $entryIdentifiers[] = $directoryIterator->getFilename();
                }
            }
        }
        return $entryIdentifiers;
    }

    /**
     * Removes all cache entries of this cache and sets the frozen flag to FALSE.
     */
    public function flush()
    {
        parent::flush();
        if ($this->frozen === true) {
            $this->frozen = false;
        }
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     */
    public function flushByTag($tag)
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        if (empty($identifiers)) {
            return;
        }
        foreach ($identifiers as $entryIdentifier) {
            $this->remove($entryIdentifier);
        }
    }

    /**
     * Checks if the given cache entry files are still valid or if their
     * lifetime has exceeded.
     *
     * @param string $cacheEntryPathAndFilename
     * @return bool
     */
    protected function isCacheFileExpired($cacheEntryPathAndFilename)
    {
        if (file_exists($cacheEntryPathAndFilename) === false) {
            return true;
        }
        $index = (int)file_get_contents(
            $cacheEntryPathAndFilename,
            false,
            null,
            filesize($cacheEntryPathAndFilename) - self::DATASIZE_DIGITS,
            self::DATASIZE_DIGITS
        );
        $expiryTime = (int)file_get_contents($cacheEntryPathAndFilename, false, null, $index, self::EXPIRYTIME_LENGTH);
        return $expiryTime !== 0 && $expiryTime < $GLOBALS['EXEC_TIME'];
    }

    /**
     * Does garbage collection
     */
    public function collectGarbage()
    {
        if ($this->frozen === true) {
            return;
        }
        for ($directoryIterator = new \DirectoryIterator($this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
            if ($directoryIterator->isDot()) {
                continue;
            }
            if ($this->isCacheFileExpired($directoryIterator->getPathname())) {
                $cacheEntryFileExtensionLength = strlen($this->cacheEntryFileExtension);
                if ($cacheEntryFileExtensionLength > 0) {
                    $this->remove(substr($directoryIterator->getFilename(), 0, -$cacheEntryFileExtensionLength));
                } else {
                    $this->remove($directoryIterator->getFilename());
                }
            }
        }
    }

    /**
     * Tries to find the cache entry for the specified identifier.
     * Usually only one cache entry should be found - if more than one exist, this
     * is due to some error or crash.
     *
     * @param string $entryIdentifier The cache entry identifier
     * @return mixed The filenames (including path) as an array if one or more entries could be found, otherwise FALSE
     */
    protected function findCacheFilesByIdentifier($entryIdentifier)
    {
        $pattern = $this->cacheDirectory . $entryIdentifier;
        $filesFound = glob($pattern);
        if ($filesFound === false || empty($filesFound)) {
            return false;
        }
        return $filesFound;
    }

    /**
     * Loads PHP code from the cache and require_onces it right away.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @throws \InvalidArgumentException
     * @return mixed Potential return value from the include operation
     */
    public function requireOnce($entryIdentifier)
    {
        if ($this->frozen === true) {
            if (isset($this->cacheEntryIdentifiers[$entryIdentifier])) {
                return require_once $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
            }
            return false;
        }
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073036);
        }
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        return $this->isCacheFileExpired($pathAndFilename) ? false : require_once $pathAndFilename;
    }

    /**
     * Loads PHP code from the cache and require it right away.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @throws \InvalidArgumentException
     * @return mixed Potential return value from the include operation
     */
    public function require(string $entryIdentifier)
    {
        if ($this->frozen) {
            if (isset($this->cacheEntryIdentifiers[$entryIdentifier])) {
                return require $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
            }
            return false;
        }
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1532528246);
        }
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        return $this->isCacheFileExpired($pathAndFilename) ? false : require $pathAndFilename;
    }
}
