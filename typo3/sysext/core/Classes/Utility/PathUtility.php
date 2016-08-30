<?php
namespace TYPO3\CMS\Core\Utility;

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

/**
 * Class with helper functions for file paths.
 */
class PathUtility
{
    /**
     * Gets the relative path from the current used script to a given directory.
     * The allowed TYPO3 path is checked as well, thus it's not possible to go to upper levels.
     *
     * @param string $targetPath Absolute target path
     * @return NULL|string
     */
    public static function getRelativePathTo($targetPath)
    {
        return self::getRelativePath(dirname(PATH_thisScript), $targetPath);
    }

    /**
     * Creates an absolute URL out of really any input path, removes '../' parts for the targetPath
     *
     * @param string $targetPath can be "../typo3conf/ext/myext/myfile.js" or "/myfile.js"
     * @return string something like "/mysite/typo3conf/ext/myext/myfile.js"
     */
    public static function getAbsoluteWebPath($targetPath)
    {
        if (self::isAbsolutePath($targetPath)) {
            if (StringUtility::beginsWith($targetPath, PATH_site)) {
                $targetPath = self::stripPathSitePrefix($targetPath);
                if (!defined('TYPO3_cliMode')) {
                    $targetPath = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . $targetPath;
                }
            }
        } elseif (strpos($targetPath, '://') !== false) {
            return $targetPath;
        } else {
            // Make an absolute path out of it
            $targetPath = GeneralUtility::resolveBackPath(dirname(PATH_thisScript) . '/' . $targetPath);
            $targetPath = self::stripPathSitePrefix($targetPath);
            if (!defined('TYPO3_cliMode')) {
                $targetPath = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . $targetPath;
            }
        }
        return $targetPath;
    }

    /**
     * Gets the relative path from a source directory to a target directory.
     * The allowed TYPO3 path is checked as well, thus it's not possible to go to upper levels.
     *
     * @param string $sourcePath Absolute source path
     * @param string $targetPath Absolute target path
     * @return NULL|string
     */
    public static function getRelativePath($sourcePath, $targetPath)
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
     * @param array $paths Paths to be processed
     * @return NULL|string
     */
    public static function getCommonPrefix(array $paths)
    {
        $paths = array_map([\TYPO3\CMS\Core\Utility\GeneralUtility::class, 'fixWindowsFilePath'], $paths);
        $commonPath = null;
        if (count($paths) === 1) {
            $commonPath = array_shift($paths);
        } elseif (count($paths) > 1) {
            $parts = explode('/', array_shift($paths));
            $comparePath = '';
            $break = false;
            foreach ($parts as $part) {
                $comparePath .= $part . '/';
                foreach ($paths as $path) {
                    if (strpos($path . '/', $comparePath) !== 0) {
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
     * Sanitizes a trailing separator.
     * (e.g. 'some/path' -> 'some/path/')
     *
     * @param string $path The path to be sanitized
     * @param string $separator The separator to be used
     * @return string
     */
    public static function sanitizeTrailingSeparator($path, $separator = '/')
    {
        return rtrim($path, $separator) . $separator;
    }

    /**
     * Returns trailing name component of path
     * Since basename() is locale dependent we need to access
     * the filesystem with the same locale of the system, not
     * the rendering context.
     * @see http://www.php.net/manual/en/function.basename.php
     *
     *
     * @param string $path
     *
     * @return string
     *
     */
    public static function basename($path)
    {
        $currentLocale = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
        $basename = basename($path);
        setlocale(LC_CTYPE, $currentLocale);
        return $basename;
    }

    /**
     * Returns parent directory's path
     * Since dirname() is locale dependent we need to access
     * the filesystem with the same locale of the system, not
     * the rendering context.
     * @see http://www.php.net/manual/en/function.dirname.php
     *
     *
     * @param string $path
     *
     * @return string
     *
     */
    public static function dirname($path)
    {
        $currentLocale = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
        $dirname = dirname($path);
        setlocale(LC_CTYPE, $currentLocale);
        return $dirname;
    }

    /**
     * Returns parent directory's path
     * Since dirname() is locale dependent we need to access
     * the filesystem with the same locale of the system, not
     * the rendering context.
     * @see http://www.php.net/manual/en/function.dirname.php
     *
     *
     * @param string $path
     * @param int $options
     *
     * @return string|array
     *
     */
    public static function pathinfo($path, $options = null)
    {
        $currentLocale = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
        $pathinfo = $options == null ? pathinfo($path) : pathinfo($path, $options);
        setlocale(LC_CTYPE, $currentLocale);
        return $pathinfo;
    }

    /**
     * Checks if the $path is absolute or relative (detecting either '/' or 'x:/' as first part of string) and returns TRUE if so.
     *
     * @param string $path File path to evaluate
     * @return bool
     */
    public static function isAbsolutePath($path)
    {
        // On Windows also a path starting with a drive letter is absolute: X:/
        if (static::isWindows() && (substr($path, 1, 2) === ':/' || substr($path, 1, 2) === ':\\')) {
            return true;
        }
        // Path starting with a / is always absolute, on every system
        return $path[0] === '/';
    }

    /**
     * Gets the (absolute) path of an include file based on the (absolute) path of a base file
     *
     * Does NOT do any sanity checks. This is a task for the calling function, e.g.
     * call GeneralUtility::getFileAbsFileName() on the result.
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName()
     *
     * Resolves all dots and slashes between that paths of both files.
     * Whether the result is absolute or not, depends of the base file name.
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
    public static function getAbsolutePathOfRelativeReferencedFileOrPath($baseFilenameOrPath, $includeFileName)
    {
        $fileName = static::basename($includeFileName);
        $basePath = substr($baseFilenameOrPath, -1) === '/' ? $baseFilenameOrPath : static::dirname($baseFilenameOrPath);
        $newDir = static::getCanonicalPath($basePath . '/' . static::dirname($includeFileName));
        // Avoid double slash on empty path
        $result = (($newDir !== '/') ? $newDir : '') . '/' . $fileName;
        return $result;
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
    public static function getCanonicalPath($path)
    {
        // Replace backslashes with slashes to work with Windows paths if given
        $path = trim(str_replace('\\', '/', $path));

        // @todo do we really need this? Probably only in testing context for vfs?
        $protocol = '';
        if (strpos($path, '://') !== false) {
            list($protocol, $path) = explode('://', $path);
            $protocol .= '://';
        }

        $absolutePathPrefix = '';
        if (static::isAbsolutePath($path)) {
            if (static::isWindows() && substr($path, 1, 2) === ':/') {
                $absolutePathPrefix = substr($path, 0, 3);
                $path = substr($path, 3);
            } else {
                $path = ltrim($path, '/');
                $absolutePathPrefix = '/';
            }
        }

        $theDirParts = explode('/', $path);
        $theDirPartsCount = count($theDirParts);
        for ($partCount = 0; $partCount < $theDirPartsCount; $partCount++) {
            // double-slashes in path: remove element
            if ($theDirParts[$partCount] === '') {
                array_splice($theDirParts, $partCount, 1);
                $partCount--;
                $theDirPartsCount--;
            }
            // "." in path: remove element
            if ($theDirParts[$partCount] === '.') {
                array_splice($theDirParts, $partCount, 1);
                $partCount--;
                $theDirPartsCount--;
            }
            // ".." in path:
            if ($theDirParts[$partCount] === '..') {
                if ($partCount >= 1) {
                    // Rremove this and previous element
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
     * Strip first part of a path, equal to the length of PATH_site
     *
     * @param string $path
     * @return string
     * @internal
     */
    public static function stripPathSitePrefix($path)
    {
        static $pathSiteLength = null;

        // calculate length when first needed
        if (!isset($pathSiteLength)) {
            $pathSiteLength = strlen(PATH_site);
        }
        return substr($path, $pathSiteLength);
    }

    /*********************
     *
     * Helper methods
     *
     *********************/

    /**
     * Wrapper method to be able to test windows path transformation on other systems
     *
     * @return bool
     */
    protected static function isWindows()
    {
        return TYPO3_OS === 'WIN';
    }
}
