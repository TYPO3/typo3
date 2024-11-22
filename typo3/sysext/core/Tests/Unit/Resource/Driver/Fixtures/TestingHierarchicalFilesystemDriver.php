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
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;

/**
 * Testing subclass of the abstract class with some protected methods exposed as public.
 */
final class TestingHierarchicalFilesystemDriver extends AbstractHierarchicalFilesystemDriver
{
    // exposed public methods

    public function canonicalizeAndCheckFileIdentifier(string $fileIdentifier): string
    {
        return parent::canonicalizeAndCheckFileIdentifier($fileIdentifier);
    }

    public function canonicalizeAndCheckFolderIdentifier(string $folderPath): string
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

    public function mergeConfigurationCapabilities(Capabilities $capabilities): Capabilities
    {
        throw new \BadMethodCallException('Not implemented', 1694348376);
    }

    public function sanitizeFileName(string $fileName): string
    {
        throw new \BadMethodCallException('Not implemented', 1694348363);
    }

    public function getRootLevelFolder(): string
    {
        throw new \BadMethodCallException('Not implemented', 1694348347);
    }

    public function getDefaultFolder(): string
    {
        throw new \BadMethodCallException('Not implemented', 1694348335);
    }

    public function getPublicUrl(string $identifier): ?string
    {
        throw new \BadMethodCallException('Not implemented', 1694348320);
    }

    public function createFolder(
        string $newFolderName,
        string $parentFolderIdentifier = '',
        bool $recursive = false
    ): string {
        throw new \BadMethodCallException('Not implemented', 1694348308);
    }

    public function renameFolder(string $folderIdentifier, string $newName): array
    {
        throw new \BadMethodCallException('Not implemented', 1694348298);
    }

    public function deleteFolder(string $folderIdentifier, bool $deleteRecursively = false): bool
    {
        throw new \BadMethodCallException('Not implemented', 1694348287);
    }

    public function fileExists(string $fileIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1694348276);
    }

    public function folderExists(string $folderIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1694348265);
    }

    public function isFolderEmpty(string $folderIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1694348253);
    }

    public function addFile(
        string $localFilePath,
        string $targetFolderIdentifier,
        string $newFileName = '',
        bool $removeOriginal = true
    ): string {
        throw new \BadMethodCallException('Not implemented', 1694348241);
    }

    public function createFile(string $fileName, string $parentFolderIdentifier): string
    {
        throw new \BadMethodCallException('Not implemented', 1694348230);
    }

    public function copyFileWithinStorage(
        string $fileIdentifier,
        string $targetFolderIdentifier,
        string $fileName
    ): string {
        throw new \BadMethodCallException('Not implemented', 1694348217);
    }

    public function renameFile(string $fileIdentifier, string $newName): string
    {
        throw new \BadMethodCallException('Not implemented', 1694348206);
    }

    public function replaceFile(string $fileIdentifier, string $localFilePath): bool
    {
        throw new \BadMethodCallException('Not implemented', 1694348194);
    }

    public function deleteFile(string $fileIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1694348183);
    }

    public function hash(string $fileIdentifier, string $hashAlgorithm): string
    {
        throw new \BadMethodCallException('Not implemented', 1694348165);
    }

    public function moveFileWithinStorage(
        string $fileIdentifier,
        string $targetFolderIdentifier,
        string $newFileName
    ): string {
        throw new \BadMethodCallException('Not implemented', 1694348145);
    }

    public function moveFolderWithinStorage(
        string $sourceFolderIdentifier,
        string $targetFolderIdentifier,
        string $newFolderName
    ): array {
        throw new \BadMethodCallException('Not implemented', 1694348132);
    }

    public function copyFolderWithinStorage(
        string $sourceFolderIdentifier,
        string $targetFolderIdentifier,
        string $newFolderName
    ): bool {
        throw new \BadMethodCallException('Not implemented', 1694348120);
    }

    public function getFileContents(string $fileIdentifier): string
    {
        throw new \BadMethodCallException('Not implemented', 1694348108);
    }

    public function setFileContents(string $fileIdentifier, string $contents): int
    {
        throw new \BadMethodCallException('Not implemented', 1694348098);
    }

    public function fileExistsInFolder(string $fileName, string $folderIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1694348086);
    }

    public function folderExistsInFolder(string $folderName, string $folderIdentifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1694348075);
    }

    public function getFileForLocalProcessing(string $fileIdentifier, bool $writable = true): string
    {
        throw new \BadMethodCallException('Not implemented', 1694348064);
    }

    public function getPermissions(string $identifier): array
    {
        throw new \BadMethodCallException('Not implemented', 1694348053);
    }

    public function dumpFileContents(string $identifier): void
    {
        // stub
    }

    public function isWithin(string $folderIdentifier, string $identifier): bool
    {
        throw new \BadMethodCallException('Not implemented', 1694348033);
    }

    public function getFileInfoByIdentifier(string $fileIdentifier, array $propertiesToExtract = []): array
    {
        throw new \BadMethodCallException('Not implemented', 1694348020);
    }

    public function getFolderInfoByIdentifier(string $folderIdentifier): array
    {
        throw new \BadMethodCallException('Not implemented', 1694348008);
    }

    public function getFileInFolder(string $fileName, string $folderIdentifier): string
    {
        throw new \BadMethodCallException('Not implemented', 1694347998);
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
        throw new \BadMethodCallException('Not implemented', 1694347973);
    }

    public function getFolderInFolder(string $folderName, string $folderIdentifier): string
    {
        throw new \BadMethodCallException('Not implemented', 1694347954);
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
        throw new \BadMethodCallException('Not implemented', 1694347938);
    }

    public function countFilesInFolder(
        string $folderIdentifier,
        bool $recursive = false,
        array $filenameFilterCallbacks = []
    ): int {
        throw new \BadMethodCallException('Not implemented', 1694347916);
    }

    public function countFoldersInFolder(
        string $folderIdentifier,
        bool $recursive = false,
        array $folderNameFilterCallbacks = []
    ): int {
        throw new \BadMethodCallException('Not implemented', 1694347891);
    }
}
