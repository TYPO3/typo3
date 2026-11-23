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

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Attribute\AsFileStorageDriver;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\SelfEmittableLazyOpenStream;
use TYPO3\CMS\Core\Resource\Capabilities;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;
use TYPO3\CMS\Core\Resource\Exception\ResourcePermissionsUnavailableException;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * A concrete implementation for a File Driver for the local file system,
 * based on the local Flysystem adapter.
 *
 * Listings follow symbolic links, files and folders are created with the
 * permissions and group configured in $GLOBALS['TYPO3_CONF_VARS']['SYS'],
 * folders are moved at once and files are processed and streamed in place.
 */
#[AsFileStorageDriver(identifier: 'Local')]
class LocalDriver extends AbstractFlysystemDriver
{
    /**
     * The absolute base path. It always contains a trailing slash.
     */
    protected string $absoluteBasePath = '/';

    protected ?bool $caseSensitiveFileSystem = null;

    /**
     * Copies get the configured permissions by fixPermissions() instead
     *
     * @var array<string, mixed>
     */
    protected array $copyOptions = [Config::OPTION_RETAIN_VISIBILITY => false];

    public function processConfiguration(): void
    {
        try {
            $this->absoluteBasePath = $this->calculateBasePath($this->configuration);
        } catch (InvalidConfigurationException $e) {
            // The storage is offline, but the absolute base path requires a "/" at the end.
            $this->absoluteBasePath = '/';
            throw $e;
        }
        parent::processConfiguration();
    }

    protected function createFilesystemAdapter(): FilesystemAdapter
    {
        $fileCreateMask = (int)octdec((string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] ?? '0664'));
        $folderCreateMask = (int)octdec((string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] ?? '2775'));
        return new LocalFilesystemAdapter(
            $this->absoluteBasePath,
            new PortableVisibilityConverter($fileCreateMask, $fileCreateMask, $folderCreateMask, $folderCreateMask, Visibility::PUBLIC),
            LOCK_EX,
            LocalFilesystemAdapter::SKIP_LINKS
        );
    }

    /**
     * Without a configured base URI, a storage below the public path is published by its path
     */
    protected function determineBaseUri(): ?string
    {
        $this->determineBaseUrl();
        return $this->baseUri;
    }

    /**
     * Uses the configured value, and detects the behaviour of the file system
     * below the base path when the option "caseSensitive" is not set.
     */
    public function isCaseSensitiveFileSystem(): bool
    {
        if (isset($this->configuration['caseSensitive'])) {
            // Values from "%env()%" placeholders arrive as strings like "false"
            return filter_var($this->configuration['caseSensitive'], FILTER_VALIDATE_BOOLEAN);
        }
        if ($this->caseSensitiveFileSystem === null) {
            $this->caseSensitiveFileSystem = $this->detectCaseSensitivity($this->absoluteBasePath);
        }
        return $this->caseSensitiveFileSystem;
    }

    /**
     * A case-insensitive file system resolves the base path with swapped case
     * as well. Paths without letters cannot be told apart and count as sensitive.
     * This matches the check TYPO3 did when it created the fileadmin storage, so
     * existing identifiers keep their case.
     */
    protected function detectCaseSensitivity(string $absoluteBasePath): bool
    {
        $swapped = strtolower($absoluteBasePath) === $absoluteBasePath ? strtoupper($absoluteBasePath) : strtolower($absoluteBasePath);
        if ($swapped === $absoluteBasePath || !is_dir($absoluteBasePath)) {
            return true;
        }
        return !is_dir($swapped);
    }

    /**
     * Determines the base URL for this driver, from the configuration or
     * the public path.
     */
    protected function determineBaseUrl(): void
    {
        // only calculate baseURI if the storage does not enforce jumpUrl Script
        if ($this->hasCapability(Capabilities::CAPABILITY_PUBLIC)) {
            if (!empty($this->configuration['baseUri'])) {
                $this->baseUri = rtrim($this->configuration['baseUri'], '/') . '/';
            } elseif (str_starts_with($this->absoluteBasePath, Environment::getPublicPath())) {
                // use site-relative URLs
                $temporaryBaseUri = rtrim(PathUtility::stripPathSitePrefix($this->absoluteBasePath), '/');
                if ($temporaryBaseUri !== '') {
                    $uriParts = explode('/', $temporaryBaseUri);
                    $uriParts = array_map(rawurlencode(...), $uriParts);
                    $temporaryBaseUri = implode('/', $uriParts) . '/';
                }
                $this->baseUri = $temporaryBaseUri;
            }
        }
    }

    /**
     * Calculates the absolute path to this driver's storage location.
     *
     * A relative base path is resolved against the project path, unless the
     * legacy "pathType" option is set to "relative", which resolves it against
     * the public path.
     */
    protected function calculateBasePath(array $configuration): string
    {
        if (!array_key_exists('basePath', $configuration) || empty($configuration['basePath'])) {
            throw new InvalidConfigurationException(
                'Configuration must contain base path.',
                1346510477
            );
        }

        $basePath = (string)$configuration['basePath'];
        if (($configuration['pathType'] ?? '') === 'relative') {
            $absoluteBasePath = Environment::getPublicPath() . '/' . $basePath;
        } elseif (PathUtility::isAbsolutePath($basePath)) {
            $absoluteBasePath = $basePath;
        } else {
            $absoluteBasePath = Environment::getProjectPath() . '/' . $basePath;
        }
        $absoluteBasePath = $this->canonicalizeAndCheckFilePath($absoluteBasePath);
        $absoluteBasePath = rtrim($absoluteBasePath, '/') . '/';
        if (!$this->isAllowedAbsolutePath($absoluteBasePath)) {
            throw new InvalidConfigurationException(
                'Base path "' . $absoluteBasePath . '" is not within the allowed project root path or allowed lockRootPath.',
                1704807715
            );
        }
        if (!is_dir($absoluteBasePath)) {
            throw new InvalidConfigurationException(
                'Base path "' . $absoluteBasePath . '" does not exist or is no directory.',
                1299233097
            );
        }
        return $absoluteBasePath;
    }

    /**
     * Creates a folder, within a given parent folder.
     * If no parent folder is given, a folder on the root-level will be created
     *
     * @param non-empty-string $newFolderName
     * @return non-empty-string the identifier of the new folder
     */
    public function createFolder(string $newFolderName, string $parentFolderIdentifier = '', bool $recursive = false): string
    {
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
        $newFolderName = trim($newFolderName, '/');
        if ($recursive === false) {
            $newFolderName = $this->sanitizeFileName($newFolderName);
            $newIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier . $newFolderName . '/');
            GeneralUtility::mkdir($this->getAbsolutePath($newIdentifier));
        } else {
            $parts = GeneralUtility::trimExplode('/', $newFolderName);
            $parts = array_map($this->sanitizeFileName(...), $parts);
            $newFolderName = implode('/', $parts);
            $newIdentifier = $this->canonicalizeAndCheckFolderIdentifier(
                $parentFolderIdentifier . $newFolderName . '/'
            );
            GeneralUtility::mkdir_deep($this->getAbsolutePath($newIdentifier));
        }
        return $newIdentifier;
    }

    /**
     * @param non-empty-string $fileName
     * @param non-empty-string $parentFolderIdentifier
     * @return non-empty-string
     */
    public function createFile(string $fileName, string $parentFolderIdentifier): string
    {
        $fileIdentifier = parent::createFile($fileName, $parentFolderIdentifier);
        GeneralUtility::fixPermissions($this->getAbsolutePath($fileIdentifier));
        return $fileIdentifier;
    }

    /**
     * @param non-empty-string $fileIdentifier
     * @return int<0, max> The number of bytes written to the file
     */
    public function setFileContents(string $fileIdentifier, string $contents): int
    {
        $result = parent::setFileContents($fileIdentifier, $contents);
        // Make sure later calls to filesize() etc. return correct values.
        clearstatcache(true, $this->getAbsolutePath($fileIdentifier));
        return $result;
    }

    /**
     * @param non-empty-string $fileIdentifier
     * @param non-empty-string $targetFolderIdentifier
     * @param non-empty-string $fileName
     * @return non-empty-string the identifier of the new file
     */
    public function copyFileWithinStorage(string $fileIdentifier, string $targetFolderIdentifier, string $fileName): string
    {
        $newIdentifier = parent::copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName);
        GeneralUtility::fixPermissions($this->getAbsolutePath($newIdentifier));
        return $newIdentifier;
    }

    /**
     * Returns information about a file.
     *
     * @param non-empty-string $fileIdentifier In the case of the LocalDriver, this is the (relative) path to the file.
     * @param list<non-empty-string> $propertiesToExtract Array of properties which should be extracted, if empty all will be extracted
     * @return array<non-empty-string, mixed>
     */
    public function getFileInfoByIdentifier(string $fileIdentifier, array $propertiesToExtract = []): array
    {
        $absoluteFilePath = $this->getAbsolutePath($fileIdentifier);
        // don't use $this->fileExists() because we need the absolute path to the file anyway, so we can directly
        // use PHP's filesystem method.
        if (!file_exists($absoluteFilePath) || !is_file($absoluteFilePath)) {
            throw new FileDoesNotExistException('File ' . $fileIdentifier . ' does not exist.', 1314516809);
        }

        $dirPath = dirname($fileIdentifier);
        $dirPath = $this->canonicalizeAndCheckFolderIdentifier($dirPath);
        return $this->extractFileInformation($absoluteFilePath, $dirPath, $propertiesToExtract);
    }

    /**
     * Returns information about a folder.
     *
     * @param string $folderIdentifier In the case of the LocalDriver, this is the (relative) path to the file.
     * @return array{
     *   identifier: non-empty-string,
     *   name: string,
     *   mtime: int,
     *   ctime: int,
     *   storage: int,
     * }
     */
    public function getFolderInfoByIdentifier(string $folderIdentifier): array
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);

        if (!$this->folderExists($folderIdentifier)) {
            throw new FolderDoesNotExistException(
                'Folder "' . $folderIdentifier . '" does not exist.',
                1314516810
            );
        }
        $absolutePath = $this->getAbsolutePath($folderIdentifier);
        return [
            'identifier' => $folderIdentifier,
            'name' => basename($folderIdentifier),
            'mtime' => filemtime($absolutePath),
            'ctime' => filectime($absolutePath),
            'storage' => $this->storageUid,
        ];
    }

    protected function getFileInformationProperty(string $fileIdentifier, string $property): bool|int|string|null
    {
        return $this->getSpecificFileInformation(
            $this->getAbsolutePath($fileIdentifier),
            $this->getParentFolderIdentifierOfIdentifier($fileIdentifier),
            $property
        );
    }

    /**
     * Extracts information about a file from the filesystem.
     *
     * @param string $filePath The absolute path to the file
     * @param string $containerPath The relative path to the file's container
     * @param array $propertiesToExtract array of properties which should be returned, if empty all will be extracted
     */
    protected function extractFileInformation(string $filePath, string $containerPath, array $propertiesToExtract = []): array
    {
        if (empty($propertiesToExtract)) {
            $propertiesToExtract = [
                'size', 'atime', 'mtime', 'ctime', 'mimetype', 'name', 'extension',
                'identifier', 'identifier_hash', 'storage', 'folder_hash',
            ];
        }
        $fileInformation = [];
        foreach ($propertiesToExtract as $property) {
            $fileInformation[$property] = $this->getSpecificFileInformation($filePath, $containerPath, $property);
        }
        return $fileInformation;
    }

    /**
     * Extracts specific information of a file from the file system.
     */
    public function getSpecificFileInformation(string $fileIdentifier, string $containerPath, string $property): bool|int|string|null
    {
        $identifier = $this->canonicalizeAndCheckFileIdentifier($containerPath . basename($fileIdentifier));

        $fileInfo = GeneralUtility::makeInstance(FileInfo::class, $fileIdentifier);
        return match ($property) {
            'size' => $fileInfo->getSize(),
            'atime' => $fileInfo->getATime(),
            'mtime' => $fileInfo->getMTime(),
            'ctime' => $fileInfo->getCTime(),
            'name' => basename($fileIdentifier),
            'extension' => pathinfo($fileIdentifier, PATHINFO_EXTENSION),
            'mimetype' => (string)$fileInfo->getMimeType(),
            'identifier' => $identifier,
            'storage' => $this->storageUid,
            'identifier_hash' => $this->hashIdentifier($identifier),
            'folder_hash' => $this->hashIdentifier($this->getParentFolderIdentifierOfIdentifier($identifier)),
            default => throw new \InvalidArgumentException(
                sprintf('The information "%s" is not available.', $property),
                1476047422
            ),
        };
    }

    /**
     * @param non-empty-string $folderIdentifier
     * @return bool TRUE if there are no files and folders within $folder
     */
    public function isFolderEmpty(string $folderIdentifier): bool
    {
        $path = $this->getAbsolutePath($folderIdentifier);
        $dirHandle = opendir($path);
        if ($dirHandle === false) {
            return true;
        }
        while ($entry = readdir($dirHandle)) {
            if ($entry !== '.' && $entry !== '..') {
                closedir($dirHandle);
                return false;
            }
        }
        closedir($dirHandle);
        return true;
    }

    /**
     * Lists the directory itself instead of using the adapter, as the adapter
     * does not follow symbolic links, which are common in local storages.
     *
     * @return \Generator<string, FileAttributes|DirectoryAttributes> identifier => attributes
     */
    protected function listDirectoryEntries(string $folderIdentifier, bool $includeFiles, bool $includeDirs, bool $recursive): \Generator
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
        $realPath = $this->getAbsolutePath($folderIdentifier);
        if (!is_dir($realPath)) {
            throw new \InvalidArgumentException(
                'Cannot list items in directory ' . $folderIdentifier . ' - does not exist or is no directory',
                1314349666
            );
        }
        $pathLength = strlen($this->getAbsoluteBasePath());
        $iteratorMode = \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::FOLLOW_SYMLINKS;
        $iterator = new \RecursiveDirectoryIterator($realPath, $iteratorMode);
        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST, \RecursiveIteratorIterator::CATCH_GET_CHILD);
        }
        /** @var \SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $isFile = $entry->isFile();
            $isDirectory = !$isFile && $entry->isDir();
            if (
                (!$isFile && !$isDirectory)
                || ($isFile && !$includeFiles)
                || ($isDirectory && !$includeDirs)
                || $entry->getFilename() === ''
                || !$entry->isReadable()
            ) {
                continue;
            }
            $path = substr($entry->getPathname(), $pathLength);
            if ($isDirectory) {
                yield '/' . $path . '/' => new DirectoryAttributes($path, null, $entry->getMTime());
            } else {
                yield '/' . $path => new FileAttributes($path, $entry->getSize(), null, $entry->getMTime());
            }
        }
    }

    /**
     * Copies folders through the adapter, but moves and renames them at once,
     * so symbolic links within are kept.
     *
     * @return array<non-empty-string, non-empty-string> map of old to new identifiers
     */
    protected function transferFolder(string $sourceFolderIdentifier, string $targetFolderIdentifier, bool $move): array
    {
        if (!$move) {
            $identifierMap = parent::transferFolder($sourceFolderIdentifier, $targetFolderIdentifier, false);
            GeneralUtility::fixPermissions($this->getAbsolutePath($targetFolderIdentifier), true);
            return $identifierMap;
        }
        $sourcePath = $this->getAbsolutePath($sourceFolderIdentifier);
        $targetPath = $this->getAbsolutePath($targetFolderIdentifier);
        // get all files and folders we are going to move, to have a map for updating later.
        $filesAndFolders = [];
        foreach ($this->listDirectoryEntries($sourceFolderIdentifier, true, true, true) as $identifier => $attributes) {
            $filesAndFolders[] = ['identifier' => $identifier, 'type' => $attributes->isDir() ? 'dir' : 'file'];
        }
        try {
            $this->getFilesystem()->move($this->getPath($sourceFolderIdentifier), $this->getPath($targetFolderIdentifier));
        } catch (FilesystemException $e) {
            throw new \RuntimeException('Moving folder ' . $sourcePath . ' to ' . $targetPath . ' failed.', 1320711817, $e);
        }
        try {
            return $this->createIdentifierMap($filesAndFolders, $sourceFolderIdentifier, $targetFolderIdentifier);
        } catch (\Exception $e) {
            @rename($targetPath, $sourcePath);
            throw new \RuntimeException(
                sprintf(
                    'Creating filename mapping after moving "%1$s" to "%2$s" failed. Reverted move operation.\\n\\nOriginal error: %3$s"',
                    $sourcePath,
                    $targetPath,
                    $e->getMessage()
                ),
                1334160746
            );
        }
    }

    /**
     * Returns the absolute path of the folder this driver operates on.
     */
    protected function getAbsoluteBasePath(): string
    {
        return $this->absoluteBasePath;
    }

    /**
     * Returns the absolute path of a file or folder.
     */
    protected function getAbsolutePath(string $fileIdentifier): string
    {
        $relativeFilePath = ltrim($this->canonicalizeAndCheckFileIdentifier($fileIdentifier), '/');
        return $this->absoluteBasePath . $relativeFilePath;
    }

    /**
     * Adds a file from the local server's hard drive to a given path in TYPO3s storage location.
     * This assumes that the local file exists, so no further check is done here.
     * After a successful "add" operation, the original file must not exist anymore.
     *
     * @param non-empty-string $localFilePath within public web path
     * @param non-empty-string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param bool $removeOriginal if set the original file will be removed after successful operation
     * @return non-empty-string the identifier of the new file
     */
    public function addFile(string $localFilePath, string $targetFolderIdentifier, string $newFileName = '', bool $removeOriginal = true): string
    {
        $localFilePath = $this->canonicalizeAndCheckFilePath($localFilePath);
        // @todo is check in storage
        if (str_starts_with($localFilePath, $this->absoluteBasePath) && $this->storageUid > 0) {
            throw new \InvalidArgumentException('Cannot add a file that is already part of this storage.', 1314778269);
        }
        $newFileName = $this->sanitizeFileName($newFileName !== '' ? $newFileName : basename($localFilePath));
        $newFileIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier) . $newFileName;
        $targetPath = $this->getAbsolutePath($newFileIdentifier);

        if ($removeOriginal) {
            if (is_uploaded_file($localFilePath)) {
                $result = @move_uploaded_file($localFilePath, $targetPath);
            } else {
                $result = @rename($localFilePath, $targetPath);
            }
        } else {
            $result = @copy($localFilePath, $targetPath);
        }
        if ($result === false || !file_exists($targetPath)) {
            throw new \RuntimeException(
                'Adding file ' . $localFilePath . ' at ' . $newFileIdentifier . ' failed.',
                1476046453
            );
        }
        clearstatcache();
        // Change the permissions of the file
        GeneralUtility::fixPermissions($targetPath);
        return $newFileIdentifier;
    }

    /**
     * Replaces the contents (and file-specific metadata) of a file with another file from the server's hard disk.
     *
     * @param non-empty-string $fileIdentifier
     * @param non-empty-string $localFilePath
     */
    public function replaceFile(string $fileIdentifier, string $localFilePath): bool
    {
        $filePath = $this->getAbsolutePath($fileIdentifier);
        if (is_uploaded_file($localFilePath)) {
            $result = @move_uploaded_file($localFilePath, $filePath);
        } else {
            $result = @rename($localFilePath, $filePath);
        }
        GeneralUtility::fixPermissions($filePath);
        if ($result === false) {
            throw new \RuntimeException('Replacing file ' . $fileIdentifier . ' with ' . $localFilePath . ' failed.', 1315314711);
        }
        return true;
    }

    /**
     * Copies a file to a temporary path and returns that path.
     */
    protected function copyFileToTemporaryPath(string $fileIdentifier): string
    {
        $sourcePath = $this->getAbsolutePath($fileIdentifier);
        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
        $result = @copy($sourcePath, $temporaryPath);
        if ($result === false) {
            throw new \RuntimeException(
                'Copying file "' . $fileIdentifier . '" to temporary path "' . $temporaryPath . '" failed.',
                1320577649
            );
        }
        @touch($temporaryPath, (int)filemtime($sourcePath));
        return $temporaryPath;
    }

    /**
     * Moves a file or folder to the given directory, renaming the source in the process if
     * a file or folder of the same name already exists in the target path.
     */
    protected function recycleFileOrFolder(string $filePath, string $recycleDirectory): bool
    {
        $destinationFile = $recycleDirectory . '/' . basename($filePath);
        if (file_exists($destinationFile)) {
            $timeStamp = \DateTimeImmutable::createFromFormat('U.u', (string)microtime(true))->format('YmdHisu');
            $destinationFile = $recycleDirectory . '/' . $timeStamp . '_' . basename($filePath);
        }
        $result = @rename($filePath, $destinationFile);
        // Update the mtime for the file, so the recycler garbage collection task knows which files to delete
        // Using ctime() is not possible there since this is not supported on Windows
        if ($result) {
            @touch($destinationFile);
        }
        return $result;
    }

    /**
     * Creates a map of old and new file/folder identifiers after renaming or
     * moving a folder. The old identifier is used as the key, the new one as the value.
     * @return array<non-empty-string, non-empty-string>
     */
    protected function createIdentifierMap(array $filesAndFolders, string $sourceFolderIdentifier, string $targetFolderIdentifier): array
    {
        $identifierMap = [];
        $identifierMap[$sourceFolderIdentifier] = $targetFolderIdentifier;
        foreach ($filesAndFolders as $oldItem) {
            $oldIdentifier = $oldItem['identifier'];
            if ($oldItem['type'] === 'dir') {
                $newIdentifier = $this->canonicalizeAndCheckFolderIdentifier(
                    str_replace($sourceFolderIdentifier, $targetFolderIdentifier, $oldItem['identifier'])
                );
            } else {
                $newIdentifier = $this->canonicalizeAndCheckFileIdentifier(
                    str_replace($sourceFolderIdentifier, $targetFolderIdentifier, $oldItem['identifier'])
                );
            }
            if (!file_exists($this->getAbsolutePath($newIdentifier))) {
                throw new FileOperationErrorException(
                    sprintf('File "%1$s" was not found (should have been copied/moved from "%2$s").', $newIdentifier, $oldIdentifier),
                    1330119453
                );
            }
            $identifierMap[$oldIdentifier] = $newIdentifier;
        }
        return $identifierMap;
    }

    /**
     * Returns (a local copy of) a file for further processing. This makes a copy
     * first when in writable mode, so if you change the file, you have to update it yourself afterward.
     *
     * @param non-empty-string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read operations.
     *                          This might speed up things, e.g. by using a cached local version.
     *                          Never modify the file if you have set this flag!
     * @return non-empty-string The path to the file on the local disk
     */
    public function getFileForLocalProcessing(string $fileIdentifier, bool $writable = true): string
    {
        if ($writable === false) {
            return $this->getAbsolutePath($fileIdentifier);
        }
        return $this->copyFileToTemporaryPath($fileIdentifier);
    }

    /**
     * Returns the permissions of a file/folder as an array (keys r, w) of boolean flags.
     *
     * @param non-empty-string $identifier
     * @return array{r: bool, w: bool}
     */
    public function getPermissions(string $identifier): array
    {
        $path = $this->getAbsolutePath($identifier);
        $permissionBits = fileperms($path);
        if ($permissionBits === false) {
            throw new ResourcePermissionsUnavailableException('Error while fetching permissions for ' . $path, 1319455097);
        }
        return [
            'r' => is_readable($path),
            'w' => is_writable($path),
        ];
    }

    protected function getPermissionsOfListedEntry(string $identifier): array
    {
        return $this->getPermissions($identifier);
    }

    /**
     * Directly output the contents of the file to the output buffer.
     * Should not take care of header files or flushing buffer before. Will be taken care of by the Storage.
     *
     * @param non-empty-string $identifier
     */
    public function dumpFileContents(string $identifier): void
    {
        readfile($this->getAbsolutePath($this->canonicalizeAndCheckFileIdentifier($identifier)));
    }

    /**
     * Stream file using a PSR-7 Response object.
     */
    public function streamFile(string $identifier, array $properties): ResponseInterface
    {
        $fileInfo = $this->getFileInfoByIdentifier($identifier, ['name', 'mimetype', 'mtime', 'size']);
        $downloadName = $properties['filename_overwrite'] ?? $fileInfo['name'] ?? '';
        $mimeType = $properties['mimetype_overwrite'] ?? $fileInfo['mimetype'] ?? '';
        $contentDisposition = ($properties['as_download'] ?? false) ? 'attachment' : 'inline';

        $filePath = $this->getAbsolutePath($this->canonicalizeAndCheckFileIdentifier($identifier));

        return new Response(
            new SelfEmittableLazyOpenStream($filePath),
            200,
            [
                'Content-Disposition' => $contentDisposition . '; filename="' . $downloadName . '"',
                'Content-Type' => $mimeType,
                'Content-Length' => (string)$fileInfo['size'],
                'Last-Modified' => gmdate('D, d M Y H:i:s', $fileInfo['mtime']) . ' GMT',
                // Cache-Control header is needed here to solve an issue with browser IE8 and lower
                // See for more information: http://support.microsoft.com/kb/323308
                'Cache-Control' => '',
            ]
        );
    }

    /**
     * Get the path of the nearest recycler folder of a given path.
     * Return an empty string if there is no recycler folder available in the base path.
     */
    protected function getRecycleDirectory(string $path): string
    {
        $recyclerSubdirectory = array_search(FolderInterface::ROLE_RECYCLER, $this->mappingFolderNameToRole, true);
        if ($recyclerSubdirectory === false) {
            return '';
        }
        $rootDirectory = rtrim($this->getAbsolutePath($this->getRootLevelFolder()), '/');
        $searchDirectory = dirname($path);
        // Check if file or folder to be deleted is inside a recycler directory
        if ($this->getRole($searchDirectory) === FolderInterface::ROLE_RECYCLER) {
            $searchDirectory = dirname($searchDirectory);
            // Check if file or folder to be deleted is inside the root recycler
            if ($searchDirectory == $rootDirectory) {
                return '';
            }
            $searchDirectory = dirname($searchDirectory);
        }
        // Search for the closest recycler directory
        while ($searchDirectory) {
            $recycleDirectory = $searchDirectory . '/' . $recyclerSubdirectory;
            if (is_dir($recycleDirectory)) {
                return $recycleDirectory;
            }
            if ($searchDirectory === $rootDirectory) {
                return '';
            }
            $searchDirectory = dirname($searchDirectory);
        }

        return '';
    }

    /**
     * Wrapper for `GeneralUtility::isAllowedAbsPath`, which implicitly invokes
     * `GeneralUtility::validPathStr` (like in `parent::isPathValid`).
     */
    protected function isAllowedAbsolutePath(string $path): bool
    {
        return GeneralUtility::isAllowedAbsPath($path);
    }
}
