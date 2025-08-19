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
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * A caching backend which stores cache entries in files, but does not support or
 * care about expiry times and tags.
 */
class SimpleFileBackend extends AbstractBackend implements PhpCapableBackendInterface
{
    /**
     * Directory where the files are stored
     */
    protected string $cacheDirectory = '';

    /**
     * Temporary path to cache directory before setCache() was called. It is
     * set by setCacheDirectory() and used in setCache() method which calls
     * the directory creation if needed. The variable is not used afterwards,
     * the final cache directory path is stored in $this->cacheDirectory then.
     */
    protected string $temporaryCacheDirectory = '';

    /**
     * A file extension to use for each cache entry.
     */
    protected string $cacheEntryFileExtension = '';

    public function setCache(FrontendInterface $cache): void
    {
        parent::setCache($cache);
        if (empty($this->temporaryCacheDirectory)) {
            // If no cache directory was given with cacheDirectory
            // configuration option, set it to a path below var/ folder
            $temporaryCacheDirectory = Environment::getVarPath() . '/';
        } else {
            $temporaryCacheDirectory = $this->temporaryCacheDirectory;
        }
        $codeOrData = $cache instanceof PhpFrontend ? 'code' : 'data';
        $finalCacheDirectory = $temporaryCacheDirectory . 'cache/' . $codeOrData . '/' . $this->cacheIdentifier . '/';
        $this->createFinalCacheDirectory($finalCacheDirectory);
        unset($this->temporaryCacheDirectory);
        $this->cacheDirectory = $finalCacheDirectory;
        $this->cacheEntryFileExtension = $cache instanceof PhpFrontend ? '.php' : '';
        if (strlen($this->cacheDirectory) + 23 > PHP_MAXPATHLEN) {
            throw new Exception('The length of the temporary cache file path "' . $this->cacheDirectory . '" exceeds the maximum path length of ' . (PHP_MAXPATHLEN - 23) . '. Please consider setting the temporaryDirectoryBase option to a shorter path.', 1248710426);
        }
    }

    /**
     * Sets the directory where the cache files are stored. By default it is
     * assumed that the directory is below TYPO3's Project Path. However, an
     * absolute path can be selected, too.
     *
     * This method enables to use a cache path outside of TYPO3's Project Path. The final
     * cache path is checked and created in createFinalCacheDirectory(),
     * called by setCache() method, which is done _after_ the cacheDirectory
     * option was handled.
     *
     * @internal Misused in tests
     * @todo: Fix tests and protect
     */
    public function setCacheDirectory(string $cacheDirectory): void
    {
        $documentRoot = Environment::getProjectPath() . '/';
        if ($open_basedir = ini_get('open_basedir')) {
            if (Environment::isWindows()) {
                $delimiter = ';';
                $cacheDirectory = str_replace('\\', '/', $cacheDirectory);
                if (!preg_match('/[A-Z]:/', substr($cacheDirectory, 0, 2))) {
                    $cacheDirectory = Environment::getProjectPath() . $cacheDirectory;
                }
            } else {
                $delimiter = ':';
                if ($cacheDirectory[0] !== '/') {
                    // relative path to cache directory.
                    $cacheDirectory = Environment::getProjectPath() . $cacheDirectory;
                }
            }
            $basedirs = explode($delimiter, $open_basedir);
            $cacheDirectoryInBaseDir = false;
            foreach ($basedirs as $basedir) {
                if (Environment::isWindows()) {
                    $basedir = str_replace('\\', '/', $basedir);
                }
                if ($basedir[strlen($basedir) - 1] !== '/') {
                    $basedir .= '/';
                }
                if (str_starts_with($cacheDirectory, $basedir)) {
                    $documentRoot = $basedir;
                    $cacheDirectory = str_replace($basedir, '', $cacheDirectory);
                    $cacheDirectoryInBaseDir = true;
                    break;
                }
            }
            if (!$cacheDirectoryInBaseDir) {
                throw new Exception(
                    'Open_basedir restriction in effect. The directory "' . $cacheDirectory . '" is not in an allowed path.',
                    1476045417
                );
            }
        } else {
            if ($cacheDirectory[0] === '/') {
                // Absolute path to cache directory.
                $documentRoot = '';
            }
            if (Environment::isWindows() && (!empty($documentRoot) && str_starts_with($cacheDirectory, $documentRoot))) {
                $documentRoot = '';
            }
        }
        // After this point all paths have '/' as directory separator
        if ($cacheDirectory[strlen($cacheDirectory) - 1] !== '/') {
            $cacheDirectory .= '/';
        }
        $this->temporaryCacheDirectory = $documentRoot . $cacheDirectory;
    }

    /**
     * Create the final cache directory if it does not exist.
     */
    protected function createFinalCacheDirectory(string $finalCacheDirectory): void
    {
        if (!is_dir($finalCacheDirectory)) {
            try {
                GeneralUtility::mkdir_deep($finalCacheDirectory);
            } catch (\RuntimeException $e) {
                throw new Exception('The directory "' . $finalCacheDirectory . '" can not be created.', 1303669848, $e);
            }
        }
        if (!is_writable($finalCacheDirectory)) {
            throw new Exception('The directory "' . $finalCacheDirectory . '" is not writable.', 1203965200);
        }
        $tmpFilesCacheDirectory = $finalCacheDirectory . 'tmp/';
        if (!is_dir($tmpFilesCacheDirectory)) {
            try {
                GeneralUtility::mkdir_deep($tmpFilesCacheDirectory);
            } catch (\RuntimeException $e) {
                throw new Exception('The temporary cache directory "' . $tmpFilesCacheDirectory . '" can not be created.', 1727176780, $e);
            }
        }
        if (!is_writable($tmpFilesCacheDirectory)) {
            throw new Exception('The temporary cache directory "' . $tmpFilesCacheDirectory . '" is not writable.', 1727176781);
        }
    }

    /**
     * Returns the directory where the cache files are stored
     *
     * @return string Full path of the cache directory
     * @internal Misused in tests
     * @todo: Fix tests and protect
     */
    public function getCacheDirectory(): string
    {
        return $this->cacheDirectory;
    }

    public function set(string $entryIdentifier, string $data, array $tags = [], ?int $lifetime = null): void
    {
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756735);
        }
        if ($entryIdentifier === '') {
            throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1334756736);
        }
        $temporaryCacheEntryPathAndFilename = $this->cacheDirectory . 'tmp/' . StringUtility::getUniqueId() . '.temp';
        $result = GeneralUtility::writeFile($temporaryCacheEntryPathAndFilename, $data, true);
        if ($result === false) {
            throw new Exception('The temporary cache file "' . $temporaryCacheEntryPathAndFilename . '" could not be written.', 1334756737);
        }
        $cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        $result = @rename($temporaryCacheEntryPathAndFilename, $cacheEntryPathAndFilename);
        if ($result === false) {
            throw new Exception('The cache file "' . $cacheEntryPathAndFilename . '" could not be written.', 1727178709);
        }
        if ($this->cacheEntryFileExtension === '.php') {
            GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive($cacheEntryPathAndFilename);
        }
    }

    public function get(string $entryIdentifier): false|string
    {
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756877);
        }
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        if (!file_exists($pathAndFilename)) {
            return false;
        }
        return file_get_contents($pathAndFilename);
    }

    public function has(string $entryIdentifier): bool
    {
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756878);
        }
        return file_exists($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
    }

    public function remove(string $entryIdentifier): bool
    {
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756960);
        }
        if ($entryIdentifier === '') {
            throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1334756961);
        }
        $file = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        return @unlink($file);
    }

    public function flush(): void
    {
        $directoryIterator = new \DirectoryIterator($this->cacheDirectory);
        foreach ($directoryIterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            if (@unlink($this->cacheDirectory . $fileInfo->getFilename())) {
                continue;
            }
            $this->logger->error('Failed to unlink cache entry: {filename}', [
                'filename' => $this->cacheDirectory . $fileInfo->getFilename(),
            ]);
        }
    }

    protected function isCacheFileExpired(string $cacheEntryPathAndFilename): bool
    {
        return file_exists($cacheEntryPathAndFilename) === false;
    }

    /**
     * No-op
     */
    public function collectGarbage(): void {}

    public function requireOnce(string $entryIdentifier): mixed
    {
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073037);
        }
        return file_exists($pathAndFilename) ? require_once $pathAndFilename : false;
    }

    public function require(string $entryIdentifier): mixed
    {
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        if ($entryIdentifier !== PathUtility::basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1532528267);
        }
        return file_exists($pathAndFilename) ? require $pathAndFilename : false;
    }
}
