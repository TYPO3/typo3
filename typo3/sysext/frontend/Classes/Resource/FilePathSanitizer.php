<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Resource;

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
     * @return string Resulting filename, is either a full absolute URL or a relative path.
     */
    public function sanitize(string $originalFileName): string
    {
        $file = trim($originalFileName);
        if (empty($file)) {
            throw new InvalidFileNameException('Empty file name given', 1530169746);
        }
        if (strpos($file, '../') !== false) {
            throw new InvalidPathException('File path "' . $file . '" contains illegal string "../"', 1530169814);
        }
        // if this is an URL, it can be returned directly
        $urlScheme = parse_url($file, PHP_URL_SCHEME);
        if ($urlScheme === 'https' || $urlScheme === 'http' || is_file(Environment::getPublicPath() . '/' . $file)) {
            return $file;
        }

        // this call also resolves EXT:myext/ files
        $file = GeneralUtility::getFileAbsFileName($file);
        if (!$file || is_dir($file)) {
            throw new FileDoesNotExistException('File "' . $originalFileName . '" was not found', 1530169845);
        }

        $file = PathUtility::stripPathSitePrefix($file);

        // Check if the found file is in the allowed paths
        foreach ($this->allowedPaths as $allowedPath) {
            if (strpos((string)$file, (string)$allowedPath, 0) === 0) {
                return $file;
            }
        }
        throw new InvalidFileException('"' . $file . '" was not located in the allowed paths', 1530169955);
    }
}
