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

use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;

/**
 * Testing subclass of the abstract class.
 */
final class TestingDriver extends AbstractDriver
{
    protected function canonicalizeAndCheckFilePath($filePath)
    {
        throw new \BadMethodCallException('Not implemented', 1691577284);
    }

    protected function canonicalizeAndCheckFileIdentifier($fileIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577288);
    }

    protected function canonicalizeAndCheckFolderIdentifier($folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577293);
    }

    public function processConfiguration()
    {
        // stub
    }

    public function initialize()
    {
        // stub
    }

    public function mergeConfigurationCapabilities($capabilities)
    {
        throw new \BadMethodCallException('Not implemented', 1691577300);
    }

    public function sanitizeFileName($fileName, $charset = '')
    {
        throw new \BadMethodCallException('Not implemented', 1691577304);
    }

    public function getRootLevelFolder()
    {
        throw new \BadMethodCallException('Not implemented', 1691577309);
    }

    public function getDefaultFolder()
    {
        throw new \BadMethodCallException('Not implemented', 1691577316);
    }

    public function getParentFolderIdentifierOfIdentifier($fileIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577324);
    }

    public function getPublicUrl($identifier): ?string
    {
        throw new \BadMethodCallException('Not implemented', 1691577328);
    }

    public function createFolder(
        $newFolderName,
        $parentFolderIdentifier = '',
        $recursive = false
    ) {
        throw new \BadMethodCallException('Not implemented', 1691577334);
    }

    public function renameFolder($folderIdentifier, $newName)
    {
        throw new \BadMethodCallException('Not implemented', 1691577338);
    }

    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        throw new \BadMethodCallException('Not implemented', 1691577342);
    }

    public function fileExists($fileIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577347);
    }

    public function folderExists($folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577350);
    }

    public function isFolderEmpty($folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577354);
    }

    public function addFile(
        $localFilePath,
        $targetFolderIdentifier,
        $newFileName = '',
        $removeOriginal = true
    ) {
        throw new \BadMethodCallException('Not implemented', 1691577360);
    }

    public function createFile($fileName, $parentFolderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577364);
    }

    public function copyFileWithinStorage(
        $fileIdentifier,
        $targetFolderIdentifier,
        $fileName
    ) {
        throw new \BadMethodCallException('Not implemented', 1691577369);
    }

    public function renameFile($fileIdentifier, $newName)
    {
        throw new \BadMethodCallException('Not implemented', 1691577375);
    }

    public function replaceFile($fileIdentifier, $localFilePath)
    {
        throw new \BadMethodCallException('Not implemented', 1691577379);
    }

    public function deleteFile($fileIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577384);
    }

    public function hash($fileIdentifier, $hashAlgorithm)
    {
        throw new \BadMethodCallException('Not implemented', 1691577388);
    }

    public function moveFileWithinStorage(
        $fileIdentifier,
        $targetFolderIdentifier,
        $newFileName
    ) {
        throw new \BadMethodCallException('Not implemented', 1691577393);
    }

    public function moveFolderWithinStorage(
        $sourceFolderIdentifier,
        $targetFolderIdentifier,
        $newFolderName
    ) {
        throw new \BadMethodCallException('Not implemented', 1691577398);
    }

    public function copyFolderWithinStorage(
        $sourceFolderIdentifier,
        $targetFolderIdentifier,
        $newFolderName
    ) {
        throw new \BadMethodCallException('Not implemented', 1691577402);
    }

    public function getFileContents($fileIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577406);
    }

    public function setFileContents($fileIdentifier, $contents)
    {
        throw new \BadMethodCallException('Not implemented', 1691577411);
    }

    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577414);
    }

    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577418);
    }

    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        throw new \BadMethodCallException('Not implemented', 1691577423);
    }

    public function getPermissions($identifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577427);
    }

    public function dumpFileContents($identifier)
    {
        // stub
    }

    public function isWithin($folderIdentifier, $identifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577435);
    }

    public function getFileInfoByIdentifier($fileIdentifier, $propertiesToExtract = [])
    {
        throw new \BadMethodCallException('Not implemented', 1691577440);
    }

    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577444);
    }

    public function getFileInFolder($fileName, $folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577449);
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
        throw new \BadMethodCallException('Not implemented', 1691577453);
    }

    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        throw new \BadMethodCallException('Not implemented', 1691577457);
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
        throw new \BadMethodCallException('Not implemented', 1691577462);
    }

    public function countFilesInFolder(
        $folderIdentifier,
        $recursive = false,
        $filenameFilterCallbacks = []
    ) {
        throw new \BadMethodCallException('Not implemented', 1691577466);
    }

    public function countFoldersInFolder(
        $folderIdentifier,
        $recursive = false,
        $folderNameFilterCallbacks = []
    ) {
        throw new \BadMethodCallException('Not implemented', 1691577470);
    }
}
