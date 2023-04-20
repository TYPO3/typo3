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

namespace TYPO3\CMS\Core\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;

/**
 * Class with helper functions for file paths.
 */
class PathUtility
{
    /**
     * Gets the relative path from the current used script to a given directory.
     *
     * The allowed TYPO3 path is checked as well, thus it's not possible to go to upper levels.
     */
    public static function getRelativePathTo(string $absolutePath): ?string
    {
        return self::getRelativePath(self::dirname(Environment::getCurrentScript()), $absolutePath);
    }

    /**
     * Creates an absolute URL out of really any input path, removes '../' parts for the targetPath
     *
     * TODO: And this exactly is a big issue as it mixes file system paths with (relative) URLs
     * TODO: Additionally it depends on the current request and can not do its job on CLI
     * TODO: deprecate entirely and replace with stricter API
     *
     * Until we have a replacement for this API, the safest way to call this method is by providing absolute filesystem paths
     * and use \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath whenever possible.
     *
     * @param string $targetPath can be "../typo3conf/ext/myext/myfile.js" or "/myfile.js"
     * @param bool $prefixWithSitePath Don't use this argument. It is only used by TYPO3 in one place, which are subject to removal.
     * @return string something like "/mysite/typo3conf/ext/myext/myfile.js"
     */
    public static function getAbsoluteWebPath(string $targetPath, bool $prefixWithSitePath = true): string
    {
        if (static::hasProtocolAndScheme($targetPath)) {
            return $targetPath;
        }

        $prefixWithSitePath = $prefixWithSitePath && !Environment::isCli();
        if (self::isAbsolutePath($targetPath)) {
            if (str_starts_with($targetPath, Environment::getPublicPath())) {
                // It is an absolute file system path with file/folder inside document root,
                // therefore we can strip the full file system path to the document root to obtain the URI
                $targetPath = self::stripPathSitePrefix($targetPath);
            } elseif (Environment::isComposerMode() && str_contains($targetPath, 'Resources/Public') && str_starts_with($targetPath, Environment::getComposerRootPath())) {
                // TYPO3 is in managed by Composer and it is an absolute file system path inside composer root path,
                // and a public resource is referenced, therefore we can calculate the path to the published assets
                // This is true for all Composer packages that are installed in vendor folder by Composer, but still recognized by TYPO3
                $relativePath = substr($targetPath, strlen(Environment::getComposerRootPath()));
                [$relativePrefix, $relativeAssetPath] = explode('Resources/Public', $relativePath);
                $targetPath = '_assets/' . md5($relativePrefix) . $relativeAssetPath;
            } else {
                // At this point it can be ANY path, even an invalid or non existent and it is totally unclear,
                // whether this is a mistake or accidentally working as intended.
                // The only conclusion here is, that this API has to be deprecated altogether an be replaced with API
                // that clearly distinguishes between creating a URL from a static resource and ensuring an URL is absolute and not relative to current script.
                $prefixWithSitePath = false;
            }
        } else {
            // Make an absolute path out of it
            $targetPath = GeneralUtility::resolveBackPath(self::dirname(Environment::getCurrentScript()) . '/' . $targetPath);
            $targetPath = self::stripPathSitePrefix($targetPath);
        }

        if ($prefixWithSitePath) {
            $targetPath = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . $targetPath;
        }

        return $targetPath;
    }

    /**
     * Dedicated method to resolve the path of public extension resources
     *
     * @internal This method should not be used for now except for TYPO3 core. It may be removed or be changed any time
     * @param bool $prefixWithSitePath Don't use this argument. It is only used by TYPO3 in one place, which is subject to removal.
     */
    public static function getPublicResourceWebPath(string $resourcePath, bool $prefixWithSitePath = true): string
    {
        if (!self::isExtensionPath($resourcePath)) {
            throw new InvalidFileException('Resource paths must start with "EXT:"', 1630089406);
        }
        $absoluteFilePath = GeneralUtility::getFileAbsFileName($resourcePath);
        if (!str_contains($resourcePath, 'Resources/Public')) {
            if (!str_starts_with($absoluteFilePath, Environment::getPublicPath())) {
                // This will be thrown in Composer mode, when extension are installed in vendor folder
                throw new InvalidFileException(sprintf('"%s" is expected to be in public directory, but is not', $resourcePath), 1635268969);
            }
            trigger_error(sprintf('Public resource "%s" is not in extension\'s Resources/Public folder. This is deprecated and will not be supported any more in future TYPO3 versions.', $resourcePath), E_USER_DEPRECATED);
        }

        return self::getAbsoluteWebPath($absoluteFilePath, $prefixWithSitePath);
    }

    /**
     * Checks whether the given path is an extension resource
     */
    public static function isExtensionPath(string $path): bool
    {
        return str_starts_with($path, 'EXT:');
    }

    /**
     * Gets the relative path from a source directory to a target directory.
     * The allowed TYPO3 path is checked as well, thus it's not possible to go to upper levels.
     *
     * @param string $sourcePath Absolute source path
     * @param string $targetPath Absolute target path
     */
    public static function getRelativePath(string $sourcePath, string $targetPath): ?string
    {
        $relativePath = null;
        $sourcePath = rtrim(GeneralUtility::fixWindowsFilePath($sourcePath), '/');
        $targetPath = rtrim(GeneralUtility::fixWindowsFilePath($targetPath), '/');
        if ($sourcePath !== $targetPath) {
            $commonPrefix = self::getCommonPrefix([$sourcePath, $targetPath]);
            if ($commonPrefix !== null && GeneralUtility::isAllowedAbsPath($commonPrefix)) {
                $commonPrefixLength = strlen($commonPrefix);
                $resolvedSourcePath = '';
                $resolvedTargetPath = '';
                $sourcePathSteps = 0;
                if (strlen($sourcePath) > $commonPrefixLength) {
                    $resolvedSourcePath = (string)substr($sourcePath, $commonPrefixLength);
                }
                if (strlen($targetPath) > $commonPrefixLength) {
                    $resolvedTargetPath = (string)substr($targetPath, $commonPrefixLength);
                }
                if ($resolvedSourcePath !== '') {
                    $sourcePathSteps = count(explode('/', $resolvedSourcePath));
                }
                $relativePath = self::sanitizeTrailingSeparator(str_repeat('../', $sourcePathSteps) . $resolvedTargetPath);
            }
        }
        return $relativePath;
    }

    /**
     * Gets the common path prefix out of many paths.
     * + /var/www/domain.com/typo3/sysext/frontend/
     * + /var/www/domain.com/typo3/sysext/em/
     * + /var/www/domain.com/typo3/sysext/file/
     * = /var/www/domain.com/typo3/sysext/
     *
     * @param array<string> $paths Paths to be processed
     */
    public static function getCommonPrefix(array $paths): ?string
    {
        $paths = array_map(GeneralUtility::fixWindowsFilePath(...), $paths);
        $commonPath = null;
        if (count($paths) === 1) {
            $commonPath = array_shift($paths);
        } elseif (count($paths) > 1) {
            $parts = explode('/', (string)array_shift($paths));
            $comparePath = '';
            $break = false;
            foreach ($parts as $part) {
                $comparePath .= $part . '/';
                foreach ($paths as $path) {
                    if (!str_starts_with($path . '/', $comparePath)) {
                        $break = true;
                        break;
                    }
                }
                if ($break) {
                    break;
                }
                $commonPath = $comparePath;
            }
        }
        if ($commonPath !== null) {
            $commonPath = self::sanitizeTrailingSeparator($commonPath, '/');
        }
        return $commonPath;
    }

    /**
     * Normalizes a trailing separator.
     *
     * (e.g. 'some/path' -> 'some/path/')
     *
     * @param string $path The path to be sanitized
     * @param string $separator The separator to be used
     */
    public static function sanitizeTrailingSeparator(string $path, string $separator = '/'): string
    {
        return rtrim($path, $separator) . $separator;
    }

    /**
     * Returns trailing name component of path
     *
     * Since basename() is locale dependent we need to access
     * the filesystem with the same locale of the system, not
     * the rendering context.
     *
     * @see http://www.php.net/manual/en/function.basename.php
     *
     * @param string $path
     */
    public static function basename(string $path): string
    {
        $targetLocale = $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] ?? '';
        if (empty($targetLocale)) {
            return basename($path);
        }
        $currentLocale = (string)setlocale(LC_CTYPE, '0');
        setlocale(LC_CTYPE, $targetLocale);
        $basename = basename($path);
        setlocale(LC_CTYPE, $currentLocale);
        return $basename;
    }

    /**
     * Returns parent directory's path
     *
     * Since dirname() is locale dependent we need to access
     * the filesystem with the same locale of the system, not
     * the rendering context.
     *
     * @see http://www.php.net/manual/en/function.dirname.php
     *
     * @param string $path
     */
    public static function dirname(string $path): string
    {
        $targetLocale = $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] ?? '';
        if (empty($targetLocale)) {
            return dirname($path);
        }
        $currentLocale = (string)setlocale(LC_CTYPE, '0');
        setlocale(LC_CTYPE, $targetLocale);
        $dirname = dirname($path);
        setlocale(LC_CTYPE, $currentLocale);
        return $dirname;
    }

    /**
     * Returns parent directory's path
     *
     * Since pathinfo() is locale dependent we need to access
     * the filesystem with the same locale of the system, not
     * the rendering context.
     *
     * The valid flags for $options are the same as for the built-in
     * phpinfo() function.
     *
     * @see http://www.php.net/manual/en/function.pathinfo.php
     *
     * @return string|string[]
     */
    public static function pathinfo(string $path, int $options = PATHINFO_ALL): string|array
    {
        $targetLocale = $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] ?? '';
        if (empty($targetLocale)) {
            return pathinfo($path, $options);
        }
        $currentLocale = (string)setlocale(LC_CTYPE, '0');
        setlocale(LC_CTYPE, $targetLocale);
        $pathinfo = pathinfo($path, $options);
        setlocale(LC_CTYPE, $currentLocale);
        return $pathinfo;
    }

    /**
     * Checks if the $path is absolute or relative (detecting either '/' or 'x:/' as first part of string) and returns TRUE if so.
     */
    public static function isAbsolutePath(string $path): bool
    {
        // On Windows also a path starting with a drive letter is absolute: X:/
        if (Environment::isWindows() && (substr($path, 1, 2) === ':/' || substr($path, 1, 2) === ':\\')) {
            return true;
        }
        // Path starting with a / is always absolute, on every system, VFS is needed for tests
        return str_starts_with($path, '/') || str_starts_with($path, 'vfs://');
    }

    /**
     * Gets the (absolute) path of an include file based on the (absolute) path of a base file
     *
     * Does NOT do any sanity checks. This is a task for the calling function, e.g.
     * call GeneralUtility::getFileAbsFileName() on the result.
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName()
     *
     * Resolves all dots and slashes between that paths of both files.
     * Whether the result is absolute or not, depends on the base file name.
     *
     * If the include file goes higher than a relative base file, then the result
     * will contain dots as a relative part.
     * <pre>
     *   base:    abc/one.txt
     *   include: ../../two.txt
     *   result:  ../two.txt
     * </pre>
     * The exact behavior, refer to getCanonicalPath().
     *
     * @param string $baseFilenameOrPath The name of the file or a path that serves as a base; a path will need to have a '/' at the end
     * @param string $includeFileName The name of the file that is included in the file
     * @return string The (absolute) path of the include file
     */
    public static function getAbsolutePathOfRelativeReferencedFileOrPath(string $baseFilenameOrPath, string $includeFileName): string
    {
        $fileName = static::basename($includeFileName);
        $basePath = str_ends_with($baseFilenameOrPath, '/') ? $baseFilenameOrPath : static::dirname($baseFilenameOrPath);
        $newDir = static::getCanonicalPath($basePath . '/' . static::dirname($includeFileName));
        // Avoid double slash on empty path
        return (($newDir !== '/') ? $newDir : '') . '/' . $fileName;
    }

    /**
     * Returns parent directory's path
     * Early during bootstrap there is no TYPO3_CONF_VARS yet so the setting for the system locale
     * is also unavailable. The path of the parent directory is determined with a regular expression
     * to avoid issues with locales.
     *
     *
     * @return string Path without trailing slash
     */
    public static function dirnameDuringBootstrap(string $path): string
    {
        return preg_replace('#(.*)(/|\\\\)([^\\\\/]+)$#', '$1', $path);
    }

    /**
     * Returns filename part of a path
     * Early during bootstrap there is no TYPO3_CONF_VARS yet so the setting for the system locale
     * is also unavailable. The filename part is determined with a regular expression to avoid issues
     * with locales.
     */
    public static function basenameDuringBootstrap(string $path): string
    {
        return preg_replace('#.*[/\\\\]([^\\\\/]+)$#', '$1', $path);
    }

    /*********************
     *
     * Cleaning methods
     *
     *********************/
    /**
     * Resolves all dots, slashes and removes spaces after or before a path...
     *
     * @param string $path Input string
     * @return string Canonical path, always without trailing slash
     */
    public static function getCanonicalPath(string $path): string
    {
        // Replace backslashes with slashes to work with Windows paths if given
        $path = trim(str_replace('\\', '/', $path));

        // @todo do we really need this? Probably only in testing context for vfs?
        $protocol = '';
        if (str_contains($path, '://')) {
            [$protocol, $path] = explode('://', $path);
            $protocol .= '://';
        }

        $absolutePathPrefix = '';
        if (static::isAbsolutePath($path)) {
            if (Environment::isWindows() && substr($path, 1, 2) === ':/') {
                $absolutePathPrefix = substr($path, 0, 3);
                $path = substr($path, 3);
            } else {
                $path = ltrim($path, '/');
                $absolutePathPrefix = '/';
            }
        }

        $theDirParts = explode('/', $path);
        $theDirPartsCount = count($theDirParts);
        // This cannot use a foreach() as some steps skip ahead multiple elements.
        for ($partCount = 0; $partCount < $theDirPartsCount; $partCount++) {
            // double-slashes in path: remove element
            if ($theDirParts[$partCount] === '') {
                array_splice($theDirParts, $partCount, 1);
                $partCount--;
                $theDirPartsCount--;
            }
            // "." in path: remove element
            if (($theDirParts[$partCount] ?? '') === '.') {
                array_splice($theDirParts, $partCount, 1);
                $partCount--;
                $theDirPartsCount--;
            }
            // ".." in path:
            if (($theDirParts[$partCount] ?? '') === '..') {
                if ($partCount >= 1) {
                    // Remove this and previous element
                    array_splice($theDirParts, $partCount - 1, 2);
                    $partCount -= 2;
                    $theDirPartsCount -= 2;
                } elseif ($absolutePathPrefix) {
                    // can't go higher than root dir
                    // simply remove this part and continue
                    array_splice($theDirParts, $partCount, 1);
                    $partCount--;
                    $theDirPartsCount--;
                }
            }
        }

        return $protocol . $absolutePathPrefix . implode('/', $theDirParts);
    }

    /**
     * Strip first part of a path, equal to the length of public web path including trailing slash
     *
     * @internal
     */
    public static function stripPathSitePrefix(string $path): string
    {
        return substr($path, strlen(Environment::getPublicPath() . '/'));
    }

    /**
     * Tries to guess whether a given URL hast protocol and (optional) scheme.
     * Scheme relative URLs match as well.
     * Current implementation is two simple string operations.
     *
     * This is just a guess. For a more detailed validation and parsing,
     * use \TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl()
     *
     * @param string $path
     *
     * @internal
     */
    public static function hasProtocolAndScheme(string $path): bool
    {
        return str_starts_with($path, '//') || strpos($path, '://') > 0;
    }
}
