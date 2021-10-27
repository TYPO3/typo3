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

namespace TYPO3\CMS\Frontend\Resource;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Checks if a given file path is allowed to be used in TYPO3 Frontend.
 *
 * Currently allowed is:
 * - a file (which must exist) from any of the allowedPaths option, without any ".." inside the path name
 * - an external URL
 *
 * The sanitize method either returns a full URL (in case it's a valid http/https resource)
 * or a path relative to the public folder of the TYPO3 Frontend.
 */
class FilePathSanitizer
{
    /**
     * These are the only paths that are allowed for resources in TYPO3 Frontend.
     * Additional paths can be added via $GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths'], where all paths should
     * be suffixed with a slash "/".
     *
     * @var array
     */
    protected $allowedPaths = [];

    /**
     * Sets the paths from where TypoScript resources are allowed to be used:
     */
    public function __construct()
    {
        $this->allowedPaths = [
            '_assets/',
            $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'],
            'uploads/',
            'typo3temp/',
            PathUtility::stripPathSitePrefix(Environment::getBackendPath()) . '/ext/',
            PathUtility::stripPathSitePrefix(Environment::getFrameworkBasePath()) . '/',
            PathUtility::stripPathSitePrefix(Environment::getExtensionsPath()) . '/',
        ];
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths'])) {
            $paths = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths'], true);
            foreach ($paths as $path) {
                if (is_string($path)) {
                    $this->allowedPaths[] = $path;
                }
            }
        }
    }

    /**
     * Returns the reference used for the frontend inclusion, checks against allowed paths for inclusion.
     *
     * @param string $originalFileName
     * @param bool|null $allowExtensionPath
     * @return string Resulting filename, is either a full absolute URL or a relative path.
     */
    public function sanitize(string $originalFileName, ?bool $allowExtensionPath = null): string
    {
        if ($allowExtensionPath === false) {
            throw new \BadMethodCallException('$allowAbsolutePaths must be either omitted or set to true', 1633671654);
        }
        $file = trim($originalFileName);
        if (empty($file)) {
            throw new InvalidFileNameException('Empty file name given', 1530169746);
        }
        if (str_contains($file, '../')) {
            throw new InvalidPathException('File path "' . $file . '" contains illegal string "../"', 1530169814);
        }
        // if this is an URL, it can be returned directly
        $urlScheme = parse_url($file, PHP_URL_SCHEME);
        if ($urlScheme === 'https' || $urlScheme === 'http' || is_file(Environment::getPublicPath() . '/' . $file)) {
            return $file;
        }

        // this call also resolves EXT:myext/ files
        $absolutePath = GeneralUtility::getFileAbsFileName($file);
        if (!$absolutePath || is_dir($absolutePath)) {
            throw new FileDoesNotExistException('File "' . $file . '" was not found', 1530169845);
        }

        $isExtensionPath = PathUtility::isExtensionPath($file);
        if ($allowExtensionPath && $isExtensionPath) {
            return $file;
        }
        $relativePath = $this->makeRelative($absolutePath, $file, $isExtensionPath);

        // Check if the found file is in the allowed paths
        foreach ($this->allowedPaths as $allowedPath) {
            if (strpos($relativePath, $allowedPath) === 0) {
                return $relativePath;
            }
        }
        throw new InvalidFileException('"' . $relativePath . '" is not located in the allowed paths', 1530169955);
    }

    private function makeRelative(string $absoluteFilePath, string $originalFilePath, bool $isExtensionPath): string
    {
        if ($isExtensionPath) {
            return PathUtility::getPublicResourceWebPath($originalFilePath, false);
        }

        if (!str_starts_with($absoluteFilePath, Environment::getPublicPath())) {
            throw new InvalidFileException('"' . $originalFilePath . '" is expected to be in public directory, but is not', 1633674049);
        }

        return PathUtility::stripPathSitePrefix($absoluteFilePath);
    }
}
