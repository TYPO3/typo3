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

use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Contains a few classes that might be useful for hierarchical drivers.
 */
abstract class AbstractHierarchicalFilesystemDriver extends AbstractDriver
{
    /**
     * @var string
     */
    public const UNSAFE_FILENAME_CHARACTER_EXPRESSION = '\\x00-\\x2C\\/\\x3A-\\x3F\\x5B-\\x60\\x7B-\\xBF';

    /**
     * Returns a string where any character not matching [.a-zA-Z0-9_-] is
     * substituted by '_'
     * Trailing dots are removed and characters are lowercased if using
     * a case insensitive file system.
     *
     * Previously in \TYPO3\CMS\Core\Utility\File\BasicFileUtility::cleanFileName()
     *
     * @param string $fileName Input string, typically the body of a fileName
     * @return non-empty-string Output string with any characters not matching [.a-zA-Z0-9_-] is substituted by '_' and trailing dots removed
     */
    public function sanitizeFileName(string $fileName): string
    {
        $fileName = \Normalizer::normalize($fileName) ?: $fileName;
        // Allow ".", "-", 0-9, a-z, A-Z and everything beyond U+C0 (latin capital letter a with grave)
        $cleanFileName = (string)preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . ']/u', '_', trim($fileName));
        if (!$this->isCaseSensitiveFileSystem()) {
            $cleanFileName = mb_strtolower($cleanFileName, 'utf-8');
        }
        // Strip trailing dots and return
        $cleanFileName = rtrim($cleanFileName, '.');
        if ($cleanFileName === '') {
            throw new InvalidFileNameException('File name ' . $fileName . ' is invalid.', 1320288991);
        }
        return $cleanFileName;
    }

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder. It will also return
     * TRUE if both canonical identifiers are equal.
     *
     * @param non-empty-string $folderIdentifier
     * @param non-empty-string $identifier identifier to be checked against $folderIdentifier
     * @return bool TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin(string $folderIdentifier, string $identifier): bool
    {
        $folderIdentifier = $this->canonicalizeAndCheckFileIdentifier($folderIdentifier);
        $entryIdentifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        if ($folderIdentifier === $entryIdentifier) {
            return true;
        }
        // File identifier canonicalization will not modify a single slash so
        // we must not append another slash in that case.
        if ($folderIdentifier !== '/') {
            $folderIdentifier .= '/';
        }
        return str_starts_with($entryIdentifier, $folderIdentifier);
    }

    /**
     * Applies a set of filter methods to a file name to find out if it should be used or not.
     * This is used by directory listings.
     *
     * @param array $filterMethods The filter methods to use
     */
    protected function applyFilterMethodsToDirectoryItem(array $filterMethods, string $itemName, string $itemIdentifier, string $parentIdentifier): bool
    {
        foreach ($filterMethods as $filter) {
            if (is_callable($filter)) {
                $result = $filter($itemName, $itemIdentifier, $parentIdentifier, [], $this);
                // We use -1 as the "don't include“ return value, for historic reasons,
                // as call_user_func() used to return FALSE if calling the method failed.
                if ($result === -1) {
                    return false;
                }
                if ($result === false) {
                    throw new \RuntimeException(
                        'Could not apply file/folder name filter ' . $filter[0] . '::' . $filter[1],
                        1476046425
                    );
                }
            }
        }
        return true;
    }

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
     * @param non-empty-string $filePath The file path (including the file name!)
     * @return non-empty-string
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
     * @param non-empty-string $folderIdentifier The file path (including the file name!)
     * @return non-empty-string
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
     * @param non-empty-string $fileIdentifier
     * @return non-empty-string
     */
    public function getParentFolderIdentifierOfIdentifier(string $fileIdentifier): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        return rtrim(GeneralUtility::fixWindowsFilePath(dirname($fileIdentifier)), '/') . '/';
    }
}
