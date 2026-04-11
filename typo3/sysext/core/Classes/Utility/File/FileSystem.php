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

namespace TYPO3\CMS\Core\Utility\File;

use Symfony\Component\Filesystem\Exception\IOException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Most of this code is thankfully taken from \Composer\Util\Filesystem
 *
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
class FileSystem
{
    /**
     * Returns the shortest path from $from to $to
     *
     * @param  bool $directories If true, the source/target are considered to be directories
     * @throws \InvalidArgumentException
     */
    public function findShortestPath(string $from, string $to, bool $directories = false): string
    {
        if (!PathUtility::isAbsolutePath($from) || !PathUtility::isAbsolutePath($to)) {
            throw new \InvalidArgumentException(sprintf('$from (%s) and $to (%s) must be absolute paths.', $from, $to), 1765283155);
        }

        $from = PathUtility::getCanonicalPath($from);
        $to = PathUtility::getCanonicalPath($to);

        if ($directories) {
            $from = rtrim($from, '/') . '/dummy_file';
        }

        if (dirname($from) === dirname($to)) {
            return './' . basename($to);
        }

        $commonPath = $to;
        while (!str_starts_with($from . '/', $commonPath . '/') && $commonPath !== '/' && preg_match('{^[A-Z]:/?$}i', $commonPath) === 0) {
            $commonPath = str_replace('\\', '/', dirname($commonPath));
        }

        // no commonality at all
        if (!str_starts_with($from, $commonPath)) {
            return $to;
        }

        $commonPath = rtrim($commonPath, '/') . '/';
        $sourcePathDepth = substr_count((string)substr($from, strlen($commonPath)), '/');
        $commonPathCode = str_repeat('../', $sourcePathDepth);

        $result = $commonPathCode . substr($to, strlen($commonPath));
        if ($result === '') {
            return './';
        }
        return $result;
    }

    /**
     * Creates a relative symlink from $link to $target
     *
     * @param  string $target The path of the binary file to be symlinked
     * @param  string $link   The path where the symlink should be created
     */
    public function relativeSymlink(string $target, string $link): bool
    {
        if (!function_exists('symlink')) {
            return false;
        }

        $cwd = $this->getCwd();

        $relativePath = $this->findShortestPath($link, $target);
        chdir(dirname($link));
        $result = @symlink($relativePath, $link);

        chdir($cwd);

        return $result;
    }

    /**
     * Return true if that directory is a symlink.
     */
    public function isSymlinkedDirectory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $resolved = $this->resolveSymlinkedDirectorySymlink($directory);

        return is_link($resolved);
    }

    /**
     * Return true if that file is a symlink.
     */
    public function isSymlinkedFile(string $file): bool
    {
        if (!is_file($file)) {
            return false;
        }
        return is_link($file);
    }

    /**
     * Creates an NTFS junction.
     */
    public function junction(string $target, string $junction): void
    {
        if (!Environment::isWindows()) {
            throw new \LogicException(sprintf('Function %s is not available on non-Windows platform', __CLASS__), 1765283168);
        }
        if (!is_dir($target)) {
            throw new IOException(sprintf('Cannot junction to "%s" as it is not a directory.', $target), 1765283131, null, $target);
        }

        // Removing any previously junction to ensure clean execution.
        if (!is_dir($junction) || $this->isJunction($junction)) {
            @rmdir($junction);
        }
        $commandLine = [
            'mklink',
            '/J',
        ];
        $commandLine[] = str_replace('/', DIRECTORY_SEPARATOR, $junction);
        $commandLine[] = realpath($target);
        CommandUtility::exec($commandLine);

        if (CommandUtility::exec($commandLine) === false) {
            throw new IOException(sprintf('Failed to create junction to "%s" at "%s".', $target, $junction), 1763664408, null, $target);
        }
        clearstatcache(true, $junction);
    }

    /**
     * Returns whether the target directory is a Windows NTFS Junction.
     *
     * We test if the path is a directory and not an ordinary link, then check
     * that the mode value returned from lstat (which gives the status of the
     * link itself) is not a directory, by replicating the POSIX S_ISDIR test.
     *
     * @param  string $junction Path to check.
     */
    public function isJunction(string $junction): bool
    {
        if (!Environment::isWindows()) {
            return false;
        }

        // Important to clear all caches first
        clearstatcache(true, $junction);

        if (!is_dir($junction) || is_link($junction)) {
            return false;
        }

        $stat = lstat($junction);

        // S_ISDIR test (S_IFDIR is 0x4000, S_IFMT is 0xF000 bitmask)
        return is_array($stat) && ($stat['mode'] & 0xF000) !== 0x4000;
    }

    /**
     * Resolve pathname to symbolic link of a directory
     *
     * @param string $pathname Directory path to resolve
     */
    private function resolveSymlinkedDirectorySymlink(string $pathname): string
    {
        if (!is_dir($pathname)) {
            return $pathname;
        }

        $resolved = rtrim($pathname, '/');

        if ($resolved === '') {
            return $pathname;
        }

        return $resolved;
    }

    /**
     * getcwd() equivalent which always returns a string
     *
     * @throws \RuntimeException
     */
    private function getCwd(): string
    {
        $cwd = getcwd();
        // fallback to realpath('') just in case this works but odds are it would break as well if we are in a case where getcwd fails
        if ($cwd === false) {
            $cwd = realpath('');
        }
        // crappy state, assume '' and hopefully relative paths allow things to continue
        if ($cwd === false) {
            throw new \RuntimeException('Could not determine the current working directory', 1765283181);
        }
        return $cwd;
    }
}
