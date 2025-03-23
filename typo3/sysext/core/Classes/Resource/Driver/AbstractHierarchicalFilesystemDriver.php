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

namespace TYPO3\CMS\Core\Resource\Driver;

use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Contains a few classes that might be useful for hierarchical drivers.
 */
abstract class AbstractHierarchicalFilesystemDriver extends AbstractDriver
{
    /**
     * Wrapper for \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr()
     *
     * @return bool TRUE if no '/', '..' or '\' is in the $theFile
     */
    protected function isPathValid(string $theFile): bool
    {
        return GeneralUtility::validPathStr($theFile);
    }

    /**
     * Makes sure the given path is valid.
     *
     * @phpstan-param non-empty-string $filePath The file path (including the file name!)
     * @phpstan-return non-empty-string
     */
    protected function canonicalizeAndCheckFilePath(string $filePath): string
    {
        $filePath = PathUtility::getCanonicalPath($filePath);
        // $filePath must be valid
        if (!$this->isPathValid($filePath)) {
            throw new InvalidPathException('File ' . $filePath . ' is not valid (".." and "//" is not allowed in path).', 1320286857);
        }
        return $filePath;
    }

    /**
     * Makes sure the Path given as parameter is valid.
     *
     * @param string $fileIdentifier The file path (including the file name!)
     */
    protected function canonicalizeAndCheckFileIdentifier(string $fileIdentifier): string
    {
        if ($fileIdentifier !== '') {
            $fileIdentifier = $this->canonicalizeAndCheckFilePath($fileIdentifier);
            $fileIdentifier = '/' . ltrim($fileIdentifier, '/');
            if (!$this->isCaseSensitiveFileSystem()) {
                $fileIdentifier = mb_strtolower($fileIdentifier, 'utf-8');
            }
        }
        return $fileIdentifier;
    }

    /**
     * Makes sure the Path given as parameter is valid.
     *
     * @phpstan-param non-empty-string $folderIdentifier The file path (including the file name!)
     * @phpstan-return non-empty-string
     */
    protected function canonicalizeAndCheckFolderIdentifier(string $folderIdentifier): string
    {
        if ($folderIdentifier === '/') {
            return '/';
        }
        return rtrim($this->canonicalizeAndCheckFileIdentifier($folderIdentifier), '/') . '/';
    }

    /**
     * Returns the identifier of the folder the file resides in.
     *
     * @phpstan-param non-empty-string $fileIdentifier
     * @phpstan-return non-empty-string
     */
    public function getParentFolderIdentifierOfIdentifier(string $fileIdentifier): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        return rtrim(GeneralUtility::fixWindowsFilePath(PathUtility::dirname($fileIdentifier)), '/') . '/';
    }
}
