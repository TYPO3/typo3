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

use TYPO3\CMS\Core\Resource\Capabilities;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;

/**
 * Testing subclass of the abstract class.
 */
final class TestingDriver extends AbstractDriver
{
    protected function canonicalizeAndCheckFilePath(string $filePath): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577284);
    }

    protected function canonicalizeAndCheckFileIdentifier(string $fileIdentifier): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577288);
    }

    protected function canonicalizeAndCheckFolderIdentifier(string $folderIdentifier): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577293);
    }

    public function processConfiguration(): void
    {
        // stub
    }

    public function initialize(): void
    {
        // stub
    }

    public function mergeConfigurationCapabilities(Capabilities $capabilities): Capabilities
    {
        throw new \BadMethodCallException('Not implemented', 1691577300);
    }

    public function sanitizeFileName(string $fileName): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577304);
    }

    public function getRootLevelFolder(): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577309);
    }

    public function getDefaultFolder(): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577316);
    }

    public function getParentFolderIdentifierOfIdentifier(string $fileIdentifier): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577324);
    }

    public function getPublicUrl(string $identifier): ?string
    {
        throw new \BadMethodCallException('Not implemented', 1691577328);
    }

    public function createFolder(
        string $newFolderName,
        string $parentFolderIdentifier = '',
        bool $recursive = false
    ): string {
        throw new \BadMethodCallException('Not implemented', 1691577334);
    }

    public function renameFolder(string $folderIdentifier, string $newName): array
    {
        throw new \BadMethodCallException('Not implemented', 1691577338);
    }

    public function deleteFolder(string $folderIdentifier, bool $deleteRecursively = false): bool
    {
        throw new \BadMethodCallException('Not implemented', 1691577342);
    }

    public function fileExists(string $fileIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1691577347);
    }

    public function folderExists(string $folderIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1691577350);
    }

    public function isFolderEmpty(string $folderIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1691577354);
    }

    public function addFile(
        string $localFilePath,
        string $targetFolderIdentifier,
        string $newFileName = '',
        bool $removeOriginal = true
    ): string {
        throw new \BadMethodCallException('Not implemented', 1691577360);
    }

    public function createFile(string $fileName, string $parentFolderIdentifier): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577364);
    }

    public function copyFileWithinStorage(
        string $fileIdentifier,
        string $targetFolderIdentifier,
        string $fileName
    ): string {
        throw new \BadMethodCallException('Not implemented', 1691577369);
    }

    public function renameFile(string $fileIdentifier, string $newName): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577375);
    }

    public function replaceFile(string $fileIdentifier, string $localFilePath): bool
    {
        throw new \BadMethodCallException('Not implemented', 1691577379);
    }

    public function deleteFile(string $fileIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1691577384);
    }

    public function hash(string $fileIdentifier, string $hashAlgorithm): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577388);
    }

    public function moveFileWithinStorage(
        string $fileIdentifier,
        string $targetFolderIdentifier,
        string $newFileName
    ): string {
        throw new \BadMethodCallException('Not implemented', 1691577393);
    }

    public function moveFolderWithinStorage(
        string $sourceFolderIdentifier,
        string $targetFolderIdentifier,
        string $newFolderName
    ): array {
        throw new \BadMethodCallException('Not implemented', 1691577398);
    }

    public function copyFolderWithinStorage(
        string $sourceFolderIdentifier,
        string $targetFolderIdentifier,
        string $newFolderName
    ): bool {
        throw new \BadMethodCallException('Not implemented', 1691577402);
    }

    public function getFileContents(string $fileIdentifier): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577406);
    }

    public function setFileContents(string $fileIdentifier, string $contents): int
    {
        throw new \BadMethodCallException('Not implemented', 1691577411);
    }

    public function fileExistsInFolder(string $fileName, string $folderIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1691577414);
    }

    public function folderExistsInFolder(string $folderName, string $folderIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1691577418);
    }

    public function getFileForLocalProcessing(string $fileIdentifier, bool $writable = true): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577423);
    }

    public function getPermissions(string $identifier): array
    {
        throw new \BadMethodCallException('Not implemented', 1691577427);
    }

    public function dumpFileContents(string $identifier): void
    {
        // stub
    }

    public function isWithin(string $folderIdentifier, string $identifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1691577435);
    }

    public function getFileInfoByIdentifier(string $fileIdentifier, array $propertiesToExtract = []): array
    {
        throw new \BadMethodCallException('Not implemented', 1691577440);
    }

    public function getFolderInfoByIdentifier(string $folderIdentifier): array
    {
        throw new \BadMethodCallException('Not implemented', 1691577444);
    }

    public function getFileInFolder(string $fileName, string $folderIdentifier): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577449);
    }

    public function getFilesInFolder(
        string $folderIdentifier,
        int $start = 0,
        int $numberOfItems = 0,
        bool $recursive = false,
        array $filenameFilterCallbacks = [],
        string $sort = '',
        bool $sortRev = false
    ): array {
        throw new \BadMethodCallException('Not implemented', 1691577453);
    }

    public function getFolderInFolder(string $folderName, string $folderIdentifier): string
    {
        throw new \BadMethodCallException('Not implemented', 1691577457);
    }

    public function getFoldersInFolder(
        string $folderIdentifier,
        int $start = 0,
        int $numberOfItems = 0,
        bool $recursive = false,
        array $folderNameFilterCallbacks = [],
        string $sort = '',
        bool $sortRev = false
    ): array {
        throw new \BadMethodCallException('Not implemented', 1691577462);
    }

    public function countFilesInFolder(
        string $folderIdentifier,
        bool $recursive = false,
        array $filenameFilterCallbacks = []
    ): int {
        throw new \BadMethodCallException('Not implemented', 1691577466);
    }

    public function countFoldersInFolder(
        string $folderIdentifier,
        bool $recursive = false,
        array $folderNameFilterCallbacks = []
    ): int {
        throw new \BadMethodCallException('Not implemented', 1691577470);
    }
}
