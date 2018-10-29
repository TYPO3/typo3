<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\IO;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\PharStreamWrapper\Exception;

class PharStreamWrapperInterceptor implements \TYPO3\PharStreamWrapper\Assertable
{
    /**
     * Asserts the given path of a Phar file is located in a valid path
     * in typo3conf/ext/* of the local TYPO3 installation.
     *
     * @param string $path
     * @param string $command
     * @return bool
     * @throws Exception
     */
    public function assert(string $path, string $command): bool
    {
        if ($this->isAllowed($path) === true) {
            return true;
        }
        throw new Exception(
            sprintf('Executing %s is denied', $path),
            1530103998
        );
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isAllowed(string $path): bool
    {
        $path = $this->determineBaseFile($path);
        if (!GeneralUtility::isAbsPath($path)) {
            $path = Environment::getPublicPath() . '/' . $path;
        }

        if (GeneralUtility::validPathStr($path)
            && GeneralUtility::isFirstPartOfStr(
                $path,
                Environment::getExtensionsPath()
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Normalizes a path, removes phar:// prefix, fixes Windows directory
     * separators. Result is without trailing slash.
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        return rtrim(
            PathUtility::getCanonicalPath(
                GeneralUtility::fixWindowsFilePath(
                    $this->removePharPrefix($path)
                )
            ),
            '/'
        );
    }

    /**
     * @param string $path
     * @return string
     */
    protected function removePharPrefix(string $path): string
    {
        return preg_replace('#^phar://#i', '', $path);
    }

    /**
     * Determines base file that can be accessed using the regular file system.
     * For e.g. "phar:///home/user/bundle.phar/content.txt" that would result
     * into "/home/user/bundle.phar".
     *
     * @param string $path
     * @return string|null
     */
    protected function determineBaseFile(string $path)
    {
        $parts = explode('/', $this->normalizePath($path));

        while (count($parts)) {
            $currentPath = implode('/', $parts);
            if (@file_exists($currentPath)) {
                return $currentPath;
            }
            array_pop($parts);
        }

        return null;
    }
}
