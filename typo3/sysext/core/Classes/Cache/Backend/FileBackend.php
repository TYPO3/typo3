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
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * A caching backend which stores cache entries in files
 */
class FileBackend extends SimpleFileBackend implements TaggableBackendInterface
{
    protected const EXPIRYTIME_LENGTH = 14;
    protected const DATASIZE_DIGITS = 10;

    /**
     * @throws Exception if the directory does not exist or is not writable or exceeds the maximum allowed path length, or if no cache frontend has been set.
     */
    public function set(string $entryIdentifier, string $data, array $tags = [], ?int $lifetime = null): void
    {
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073032);
        }
        if ($entryIdentifier === '') {
            throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1298114280);
        }
        $this->remove($entryIdentifier);
        $temporaryCacheEntryPathAndFilename = $this->cacheDirectory . StringUtility::getUniqueId() . '.temp';
        $lifetime ??= $this->defaultLifetime;
        $expiryTime = $lifetime === 0 ? 0 : (int)($GLOBALS['EXEC_TIME'] + $lifetime);
        $metaData = str_pad((string)$expiryTime, self::EXPIRYTIME_LENGTH) . implode(' ', $tags) . str_pad((string)strlen($data), self::DATASIZE_DIGITS);
        $result = GeneralUtility::writeFile($temporaryCacheEntryPathAndFilename, $data . $metaData, true);
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
     * @return false|string The cache entry's content as a string or FALSE if the cache entry could not be loaded
     */
    public function get(string $entryIdentifier): false|string
    {
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

    public function has(string $entryIdentifier): bool
    {
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073034);
        }
        return !$this->isCacheFileExpired($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
    }

    public function findIdentifiersByTag(string $tag): array
    {
        $entryIdentifiers = [];
        $now = $GLOBALS['EXEC_TIME'];
        $cacheEntryFileExtensionLength = strlen($this->cacheEntryFileExtension);
        for ($directoryIterator = GeneralUtility::makeInstance(\DirectoryIterator::class, $this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
            if (!$directoryIterator->isFile()) {
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
            if (in_array($tag, explode(' ', substr($metaData, self::EXPIRYTIME_LENGTH, -self::DATASIZE_DIGITS)))) {
                if ($cacheEntryFileExtensionLength > 0) {
                    $entryIdentifiers[] = substr((string)$directoryIterator->getFilename(), 0, -$cacheEntryFileExtensionLength);
                } else {
                    $entryIdentifiers[] = $directoryIterator->getFilename();
                }
            }
        }
        return $entryIdentifiers;
    }

    public function flushByTag(string $tag): void
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $entryIdentifier) {
            $this->remove($entryIdentifier);
        }
    }

    public function flushByTags(array $tags): void
    {
        array_walk($tags, $this->flushByTag(...));
    }

    /**
     * Checks if the given cache entry files are still valid or if their
     * lifetime has exceeded.
     */
    protected function isCacheFileExpired(string $cacheEntryPathAndFilename): bool
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

    public function collectGarbage(): void
    {
        for ($directoryIterator = new \DirectoryIterator($this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
            if (!$directoryIterator->isFile()) {
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

    public function requireOnce(string $entryIdentifier): mixed
    {
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073036);
        }
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        return $this->isCacheFileExpired($pathAndFilename) ? false : require_once $pathAndFilename;
    }

    public function require(string $entryIdentifier): mixed
    {
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1532528246);
        }
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        return $this->isCacheFileExpired($pathAndFilename) ? false : require $pathAndFilename;
    }
}
