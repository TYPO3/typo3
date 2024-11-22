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

use TYPO3\CMS\Core\Resource\Capabilities;

/**
 * An interface Drivers have to implement to fulfil the needs
 * of the FAL API.
 */
interface DriverInterface
{
    /**
     * Processes the configuration for this driver.
     */
    public function processConfiguration(): void;

    /**
     * Sets the storage uid the driver belongs to
     */
    public function setStorageUid(int $storageUid): void;

    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     */
    public function initialize(): void;

    /**
     * Returns the capabilities of this driver.
     */
    public function getCapabilities(): Capabilities;

    /**
     * Merges the capabilities merged by the user at the storage
     * configuration into the actual capabilities of the driver
     * and returns the result.
     */
    public function mergeConfigurationCapabilities(Capabilities $capabilities): Capabilities;

    /**
     * Returns TRUE if this driver has the given capability.
     *
     * @param Capabilities::CAPABILITY_* $capability
     */
    public function hasCapability(int $capability): bool;

    /**
     * Returns TRUE if this driver uses case-sensitive identifiers. NOTE: This
     * is a configurable setting, but the setting does not change the way the
     * underlying file system treats the identifiers; the setting should
     * therefore always reflect the file system and not try to change its
     * behaviour
     */
    public function isCaseSensitiveFileSystem(): bool;

    /**
     * Cleans a fileName from not allowed characters
     *
     * @param non-empty-string $fileName
     * @return non-empty-string the sanitized filename
     */
    public function sanitizeFileName(string $fileName): string;

    /**
     * Hashes a file identifier, taking the case sensitivity of the file system
     * into account. This helps mitigating problems with case-insensitive
     * databases.
     *
     * @param non-empty-string $identifier
     * @return non-empty-string
     */
    public function hashIdentifier(string $identifier): string;

    /**
     * Returns the identifier of the root level folder of the storage.
     *
     * @return non-empty-string
     */
    public function getRootLevelFolder(): string;

    /**
     * Returns the identifier of the default folder new files should be put into.
     *
     * @return non-empty-string
     */
    public function getDefaultFolder(): string;

    /**
     * Returns the identifier of the folder the file resides in
     *
     * @param non-empty-string $fileIdentifier
     * @return non-empty-string
     */
    public function getParentFolderIdentifierOfIdentifier(string $fileIdentifier): string;

    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to public web path (rawurlencoded).
     *
     * @param non-empty-string $identifier
     * @return non-empty-string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl(string $identifier): ?string;

    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a root level folder will be created
     *
     * @param non-empty-string $newFolderName
     * @return non-empty-string the Identifier of the new folder
     */
    public function createFolder(string $newFolderName, string $parentFolderIdentifier = '', bool $recursive = false): string;

    /**
     * Renames a folder in this storage.
     *
     * @param non-empty-string $folderIdentifier
     * @param non-empty-string $newName
     * @return array<string, string> A map of old to new file identifiers of all affected resources
     */
    public function renameFolder(string $folderIdentifier, string $newName): array;

    /**
     * Removes a folder in filesystem.
     *
     * @param non-empty-string $folderIdentifier
     */
    public function deleteFolder(string $folderIdentifier, bool $deleteRecursively = false): bool;

    /**
     * Checks if a file exists.
     *
     * @param non-empty-string $fileIdentifier
     */
    public function fileExists(string $fileIdentifier): bool;

    /**
     * Checks if a folder exists.
     *
     * @param non-empty-string $folderIdentifier
     */
    public function folderExists(string $folderIdentifier): bool;

    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param non-empty-string $folderIdentifier
     * @return bool TRUE if there are no files and folders within $folder
     */
    public function isFolderEmpty(string $folderIdentifier): bool;

    /**
     * Adds a file from the local server hard disk to a given path in TYPO3s
     * virtual file system. This assumes that the local file exists, so no
     * further check is done here! After a successful operation the original
     * file must not exist anymore.
     *
     * @param non-empty-string $localFilePath within public web path
     * @param non-empty-string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param bool $removeOriginal if set the original file will be removed
     *                                after successful operation
     * @return non-empty-string the identifier of the new file
     */
    public function addFile(string $localFilePath, string $targetFolderIdentifier, string $newFileName = '', bool $removeOriginal = true): string;

    /**
     * Creates a new (empty) file and returns the identifier.
     *
     * @param non-empty-string $fileName
     * @param non-empty-string $parentFolderIdentifier
     * @return non-empty-string
     */
    public function createFile(string $fileName, string $parentFolderIdentifier): string;

    /**
     * Copies a file *within* the current storage.
     * Note that this is only about an inner storage copy action,
     * where a file is just copied to another folder in the same storage.
     *
     * @param non-empty-string $fileIdentifier
     * @param non-empty-string $targetFolderIdentifier
     * @param non-empty-string $fileName
     * @return non-empty-string the Identifier of the new file
     */
    public function copyFileWithinStorage(string $fileIdentifier, string $targetFolderIdentifier, string $fileName): string;

    /**
     * Renames a file in this storage.
     *
     * @param non-empty-string $fileIdentifier
     * @param non-empty-string $newName The target path (including the file name!)
     * @return non-empty-string The identifier of the file after renaming
     */
    public function renameFile(string $fileIdentifier, string $newName): string;

    /**
     * Replaces a file with file in local file system.
     *
     * @param non-empty-string $fileIdentifier
     * @param non-empty-string $localFilePath
     */
    public function replaceFile(string $fileIdentifier, string $localFilePath): bool;

    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @param non-empty-string $fileIdentifier
     */
    public function deleteFile(string $fileIdentifier): bool;

    /**
     * Creates a hash for a file.
     *
     * @param non-empty-string $fileIdentifier
     * @param non-empty-string $hashAlgorithm The hash algorithm to use
     */
    public function hash(string $fileIdentifier, string $hashAlgorithm): string;

    /**
     * Moves a file *within* the current storage.
     * Note that this is only about an inner-storage move action,
     * where a file is just moved to another folder in the same storage.
     *
     * @param non-empty-string $fileIdentifier
     * @param non-empty-string $targetFolderIdentifier
     * @param non-empty-string $newFileName
     * @return non-empty-string
     */
    public function moveFileWithinStorage(string $fileIdentifier, string $targetFolderIdentifier, string $newFileName): string;

    /**
     * Folder equivalent to moveFileWithinStorage().
     *
     * @param non-empty-string $sourceFolderIdentifier
     * @param non-empty-string $targetFolderIdentifier
     * @param non-empty-string $newFolderName
     * @return array<non-empty-string, non-empty-string> All files which are affected, map of old => new file identifiers
     */
    public function moveFolderWithinStorage(string $sourceFolderIdentifier, string $targetFolderIdentifier, string $newFolderName): array;

    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param non-empty-string $sourceFolderIdentifier
     * @param non-empty-string $targetFolderIdentifier
     * @param non-empty-string $newFolderName
     */
    public function copyFolderWithinStorage(string $sourceFolderIdentifier, string $targetFolderIdentifier, string $newFolderName): bool;

    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     *
     * @param non-empty-string $fileIdentifier
     */
    public function getFileContents(string $fileIdentifier): string;

    /**
     * Sets the contents of a file to the specified value.
     *
     * @param non-empty-string $fileIdentifier
     * @return int<0, max> The number of bytes written to the file
     */
    public function setFileContents(string $fileIdentifier, string $contents): int;

    /**
     * Checks if a file inside a folder exists
     *
     * @param non-empty-string $fileName
     * @param non-empty-string $folderIdentifier
     */
    public function fileExistsInFolder(string $fileName, string $folderIdentifier): bool;

    /**
     * Checks if a folder inside a folder exists.
     *
     * @param non-empty-string $folderName
     * @param non-empty-string $folderIdentifier
     */
    public function folderExistsInFolder(string $folderName, string $folderIdentifier): bool;

    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     *
     * @param non-empty-string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                       operations. This might speed up things, e.g. by using
     *                       a cached local version. Never modify the file if you
     *                       have set this flag!
     * @return non-empty-string The path to the file on the local disk
     */
    public function getFileForLocalProcessing(string $fileIdentifier, bool $writable = true): string;

    /**
     * Returns the permissions of a file/folder as an array
     * (keys r, w) of boolean flags
     *
     * @param non-empty-string $identifier
     * @return array{r: bool, w: bool}
     */
    public function getPermissions(string $identifier): array;

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param non-empty-string $identifier
     */
    public function dumpFileContents(string $identifier): void;

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return TRUE if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     *
     * @param non-empty-string $folderIdentifier
     * @param non-empty-string $identifier identifier to be checked against $folderIdentifier
     * @return bool TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin(string $folderIdentifier, string $identifier): bool;

    /**
     * Returns information about a file.
     *
     * @param non-empty-string $fileIdentifier
     * @param list<string> $propertiesToExtract Array of properties which are be extracted
     *                                   If empty all will be extracted
     * @return array<string, mixed>
     */
    public function getFileInfoByIdentifier(string $fileIdentifier, array $propertiesToExtract = []): array;

    /**
     * Returns information about a folder.
     *
     * @param non-empty-string $folderIdentifier
     * @return array{
     *   identifier: non-empty-string,
     *   name: string,
     *   mtime: int,
     *   ctime: int,
     *   storage: int,
     * }
     */
    public function getFolderInfoByIdentifier(string $folderIdentifier): array;

    /**
     * Returns the identifier of a file inside the folder
     *
     * @param non-empty-string $fileName
     * @param non-empty-string $folderIdentifier
     * @return non-empty-string file identifier
     */
    public function getFileInFolder(string $fileName, string $folderIdentifier): string;

    /**
     * Returns a list of files inside the specified path
     *
     * @param non-empty-string $folderIdentifier
     * @param int<0, max> $start
     * @param int<0, max> $numberOfItems
     * @param list<callable> $filenameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return list<string> of FileIdentifiers
     */
    public function getFilesInFolder(
        string $folderIdentifier,
        int $start = 0,
        int $numberOfItems = 0,
        bool $recursive = false,
        array $filenameFilterCallbacks = [],
        string $sort = '',
        bool $sortRev = false
    ): array;

    /**
     * Returns the identifier of a folder inside the folder
     *
     * @param non-empty-string $folderName The name of the target folder
     * @param non-empty-string $folderIdentifier
     * @return non-empty-string folder identifier
     */
    public function getFolderInFolder(string $folderName, string $folderIdentifier): string;

    /**
     * Returns a list of folders inside the specified path
     *
     * @param non-empty-string $folderIdentifier
     * @param int<0, max> $start
     * @param int<0, max> $numberOfItems
     * @param list<callable> $folderNameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array<string|int, string> folder identifiers (where key and value are identical, but int-like identifiers
     *         will get converted to int array keys)
     */
    public function getFoldersInFolder(
        string $folderIdentifier,
        int $start = 0,
        int $numberOfItems = 0,
        bool $recursive = false,
        array $folderNameFilterCallbacks = [],
        string $sort = '',
        bool $sortRev = false
    ): array;

    /**
     * Returns the number of files inside the specified path
     *
     * @param non-empty-string $folderIdentifier
     * @param list<callable> $filenameFilterCallbacks callbacks for filtering the items
     * @return int<0, max> Number of files in folder
     */
    public function countFilesInFolder(string $folderIdentifier, bool $recursive = false, array $filenameFilterCallbacks = []): int;

    /**
     * Returns the number of folders inside the specified path
     *
     * @param non-empty-string $folderIdentifier
     * @param list<callable> $folderNameFilterCallbacks callbacks for filtering the items
     * @return int<0, max> Number of folders in folder
     */
    public function countFoldersInFolder(string $folderIdentifier, bool $recursive = false, array $folderNameFilterCallbacks = []): int;
}
