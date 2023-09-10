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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Driver\Fixtures;

use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;

/**
 * Testing subclass of the abstract class with some protected methods exposed as public.
 */
final class TestingHierarchicalFilesystemDriver extends AbstractHierarchicalFilesystemDriver
{
    // exposed public methods

    public function canonicalizeAndCheckFileIdentifier($fileIdentifier)
    {
        return parent::canonicalizeAndCheckFileIdentifier($fileIdentifier);
    }

    public function canonicalizeAndCheckFolderIdentifier($folderPath)
    {
        return parent::canonicalizeAndCheckFolderIdentifier($folderPath);
    }

    // implementation of abstract methods from the parent class

    public function processConfiguration(): void
    {
        // stub
    }

    public function initialize(): void
    {
        // stub
    }

    public function mergeConfigurationCapabilities($capabilities)
    {
        throw new \BadMethodCallException('Not implemented', 1694348376);
    }

    public function sanitizeFileName($fileName, $charset = '')
    {
        throw new \BadMethodCallException('Not implemented', 1694348363);
    }

    public function getRootLevelFolder()
    {
        throw new \BadMethodCallException('Not implemented', 1694348347);
    }

    public function getDefaultFolder()
    {
        throw new \BadMethodCallException('Not implemented', 1694348335);
    }

    public function getPublicUrl($identifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348320);
    }

    public function createFolder(
        $newFolderName,
        $parentFolderIdentifier = '',
        $recursive = false
    ) {
        throw new \BadMethodCallException('Not implemented', 1694348308);
    }

    public function renameFolder($folderIdentifier, $newName)
    {
        throw new \BadMethodCallException('Not implemented', 1694348298);
    }

    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        throw new \BadMethodCallException('Not implemented', 1694348287);
    }

    public function fileExists($fileIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348276);
    }

    public function folderExists($folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348265);
    }

    public function isFolderEmpty($folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348253);
    }

    public function addFile(
        $localFilePath,
        $targetFolderIdentifier,
        $newFileName = '',
        $removeOriginal = true
    ) {
        throw new \BadMethodCallException('Not implemented', 1694348241);
    }

    public function createFile($fileName, $parentFolderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348230);
    }

    public function copyFileWithinStorage(
        $fileIdentifier,
        $targetFolderIdentifier,
        $fileName
    ) {
        throw new \BadMethodCallException('Not implemented', 1694348217);
    }

    public function renameFile($fileIdentifier, $newName)
    {
        throw new \BadMethodCallException('Not implemented', 1694348206);
    }

    public function replaceFile($fileIdentifier, $localFilePath)
    {
        throw new \BadMethodCallException('Not implemented', 1694348194);
    }

    public function deleteFile($fileIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348183);
    }

    public function hash($fileIdentifier, $hashAlgorithm)
    {
        throw new \BadMethodCallException('Not implemented', 1694348165);
    }

    public function moveFileWithinStorage(
        $fileIdentifier,
        $targetFolderIdentifier,
        $newFileName
    ) {
        throw new \BadMethodCallException('Not implemented', 1694348145);
    }

    public function moveFolderWithinStorage(
        $sourceFolderIdentifier,
        $targetFolderIdentifier,
        $newFolderName
    ) {
        throw new \BadMethodCallException('Not implemented', 1694348132);
    }

    public function copyFolderWithinStorage(
        $sourceFolderIdentifier,
        $targetFolderIdentifier,
        $newFolderName
    ) {
        throw new \BadMethodCallException('Not implemented', 1694348120);
    }

    public function getFileContents($fileIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348108);
    }

    public function setFileContents($fileIdentifier, $contents)
    {
        throw new \BadMethodCallException('Not implemented', 1694348098);
    }

    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348086);
    }

    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348075);
    }

    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        throw new \BadMethodCallException('Not implemented', 1694348064);
    }

    public function getPermissions($identifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348053);
    }

    public function dumpFileContents($identifier): void
    {
        // stub
    }

    public function isWithin($folderIdentifier, $identifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348033);
    }

    public function getFileInfoByIdentifier($fileIdentifier, $propertiesToExtract = [])
    {
        throw new \BadMethodCallException('Not implemented', 1694348020);
    }

    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694348008);
    }

    public function getFileInFolder($fileName, $folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694347998);
    }

    public function getFilesInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        $filenameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ) {
        throw new \BadMethodCallException('Not implemented', 1694347973);
    }

    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1694347954);
    }

    public function getFoldersInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        $folderNameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ) {
        throw new \BadMethodCallException('Not implemented', 1694347938);
    }

    public function countFilesInFolder(
        $folderIdentifier,
        $recursive = false,
        $filenameFilterCallbacks = []
    ) {
        throw new \BadMethodCallException('Not implemented', 1694347916);
    }

    public function countFoldersInFolder(
        $folderIdentifier,
        $recursive = false,
        $folderNameFilterCallbacks = []
    ) {
        throw new \BadMethodCallException('Not implemented', 1694347891);
    }
}
