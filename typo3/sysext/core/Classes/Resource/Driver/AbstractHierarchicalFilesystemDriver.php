<?php
namespace TYPO3\CMS\Core\Resource\Driver;

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
 * Class AbstractHierarchicalFilesystemDriver
 */
abstract class AbstractHierarchicalFilesystemDriver extends AbstractDriver
{
    /**
     * Wrapper for \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr()
     *
     * @param string $theFile Filepath to evaluate
     * @return bool TRUE if no '/', '..' or '\' is in the $theFile
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr()
     */
    protected function isPathValid($theFile)
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr($theFile);
    }

    /**
     * Makes sure the Path given as parameter is valid
     *
     * @param string $filePath The file path (including the file name!)
     * @return string
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
     */
    protected function canonicalizeAndCheckFilePath($filePath)
    {
        $filePath = \TYPO3\CMS\Core\Utility\PathUtility::getCanonicalPath($filePath);

        // filePath must be valid
        // Special case is required by vfsStream in Unit Test context
        if (!$this->isPathValid($filePath) && substr($filePath, 0, 6) !== 'vfs://') {
            throw new \TYPO3\CMS\Core\Resource\Exception\InvalidPathException('File ' . $filePath . ' is not valid (".." and "//" is not allowed in path).', 1320286857);
        }
        return $filePath;
    }

    /**
     * Makes sure the Path given as parameter is valid
     *
     * @param string $fileIdentifier The file path (including the file name!)
     * @return string
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
     */
    protected function canonicalizeAndCheckFileIdentifier($fileIdentifier)
    {
        if ($fileIdentifier !== '') {
            $fileIdentifier = $this->canonicalizeAndCheckFilePath($fileIdentifier);
            $fileIdentifier = '/' . ltrim($fileIdentifier, '/');
            if (!$this->isCaseSensitiveFileSystem()) {
                $fileIdentifier = strtolower($fileIdentifier);
            }
        }
        return $fileIdentifier;
    }

    /**
     * Makes sure the Path given as parameter is valid
     *
     * @param string $folderPath The file path (including the file name!)
     * @return string
     */
    protected function canonicalizeAndCheckFolderIdentifier($folderPath)
    {
        if ($folderPath === '/') {
            $canonicalizedIdentifier = $folderPath;
        } else {
            $canonicalizedIdentifier = rtrim($this->canonicalizeAndCheckFileIdentifier($folderPath), '/') . '/';
        }
        return $canonicalizedIdentifier;
    }

    /**
     * Returns the identifier of the folder the file resides in
     *
     * @param string $fileIdentifier
     * @return mixed
     */
    public function getParentFolderIdentifierOfIdentifier($fileIdentifier)
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        return \TYPO3\CMS\Core\Utility\PathUtility::dirname($fileIdentifier) . '/';
    }
}
