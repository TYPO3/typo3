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

use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\Capabilities;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\Exception\ResourcePermissionsUnavailableException;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\PublicUrl\PrefixPublicUrlGenerator;
use TYPO3\CMS\Core\Resource\PublicUrl\PublicUrlResolverInterface;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for file storage drivers backed by a Flysystem adapter.
 *
 * A concrete driver only creates the adapter from its storage configuration,
 * everything TYPO3 needs on top of Flysystem - identifiers, file name
 * sanitizing, filters, sorting, local copies for processing and streaming -
 * is provided here. All paths are canonicalized and checked before they are
 * passed to Flysystem, which rejects path traversal on its own as well.
 *
 * The option "publicUrl" selects how the public URLs of the storage are
 * generated, its "type" is either "prefix", the default, which publishes the
 * storage below the URL of the option "baseUri", or "adapter", which takes the
 * URLs from an adapter implementing PublicUrlGenerator. Without a generator
 * the storage is not public. Adapters like the AWS S3 one generate URLs for
 * private buckets as well, so their URLs are only used if configured.
 */
abstract class AbstractFlysystemDriver extends AbstractHierarchicalFilesystemDriver implements StreamableDriverInterface, PublicUrlResolvingDriverInterface
{
    protected const int FILE_METADATA_LIMIT = 10000;

    protected ?Filesystem $filesystem = null;

    protected ?FilesystemAdapter $adapter = null;

    protected ?string $baseUri = null;

    protected ?PublicUrlGenerator $publicUrlGenerator = null;

    /**
     * Flysystem options for copying files, e.g. whether the visibility is retained
     *
     * @var array<string, mixed>
     */
    protected array $copyOptions = [];

    /**
     * Local copies of files for read-only processing, removed when the file
     * changes or the driver is destructed
     *
     * @var array<string, string>
     */
    protected array $localCopies = [];

    /**
     * Metadata of files and folders known from listings and earlier requests,
     * as a remote adapter needs a request for each property. Folders are kept
     * with their trailing slash. Discarded when it grows too large.
     *
     * @var array<string, array{exists?: true, size?: int, mtime?: int, mimetype?: string, reportedMimetype?: string}>
     */
    protected array $fileMetadata = [];

    /**
     * @var array<non-empty-string, FolderInterface::ROLE_*>
     */
    protected array $mappingFolderNameToRole = [
        '_recycler_' => FolderInterface::ROLE_RECYCLER,
        '_temp_' => FolderInterface::ROLE_TEMPORARY,
        'user_upload' => FolderInterface::ROLE_USERUPLOAD,
    ];

    public function __construct(array $configuration = [])
    {
        parent::__construct($configuration);
        $this->capabilities = new Capabilities(
            Capabilities::CAPABILITY_BROWSABLE
            | Capabilities::CAPABILITY_PUBLIC
            | Capabilities::CAPABILITY_WRITABLE
            | Capabilities::CAPABILITY_HIERARCHICAL_IDENTIFIERS
        );
    }

    public function __destruct()
    {
        foreach ($this->localCopies as $localCopy) {
            @unlink($localCopy);
        }
    }

    /**
     * Creates the Flysystem adapter from the driver configuration in $this->configuration.
     *
     * @throws InvalidConfigurationException if the configuration is incomplete or invalid
     */
    abstract protected function createFilesystemAdapter(): FilesystemAdapter;

    public function processConfiguration(): void
    {
        $this->adapter = $this->createFilesystemAdapter();
        $this->baseUri = $this->determineBaseUri();
        $this->publicUrlGenerator = $this->createPublicUrlGenerator();
        $this->filesystem = new Filesystem($this->adapter, [], new FlysystemPathNormalizer(), $this->publicUrlGenerator);
        if ($this->publicUrlGenerator === null) {
            $this->capabilities->removeCapability(Capabilities::CAPABILITY_PUBLIC);
        }
    }

    /**
     * Returns the URL the storage is published below, or null if it is not public.
     */
    protected function determineBaseUri(): ?string
    {
        if (empty($this->configuration['baseUri'])) {
            return null;
        }
        return rtrim((string)$this->configuration['baseUri'], '/') . '/';
    }

    /**
     * Creates the generator for the public URLs of the storage according to
     * the option "publicUrl", or returns null if the storage is not public.
     *
     * @throws InvalidConfigurationException
     */
    protected function createPublicUrlGenerator(): ?PublicUrlGenerator
    {
        $type = (string)($this->configuration['publicUrl']['type'] ?? 'prefix');
        if ($type === 'prefix') {
            return $this->baseUri !== null ? new PrefixPublicUrlGenerator($this->baseUri) : null;
        }
        if ($type === 'adapter') {
            if (!$this->adapter instanceof PublicUrlGenerator) {
                throw new InvalidConfigurationException(
                    sprintf('The public URLs of storage "%d" are taken from its adapter, which does not implement %s.', (int)$this->storageUid, PublicUrlGenerator::class),
                    1795458401
                );
            }
            return $this->adapter;
        }
        throw new InvalidConfigurationException(
            sprintf('The public URL type "%s" of storage "%d" is unknown, use "prefix" or "adapter".', $type, (int)$this->storageUid),
            1795458402
        );
    }

    public function initialize(): void {}

    public function mergeConfigurationCapabilities(Capabilities $capabilities): Capabilities
    {
        $this->capabilities->and($capabilities);
        return $this->capabilities;
    }

    protected function getFilesystem(): Filesystem
    {
        if ($this->filesystem === null) {
            throw new InvalidConfigurationException(
                sprintf('The Flysystem driver of storage "%d" is not configured.', (int)$this->storageUid),
                1792093589
            );
        }
        return $this->filesystem;
    }

    /**
     * Converts a canonical file or folder identifier to a Flysystem path
     */
    protected function getPath(string $identifier): string
    {
        return trim($identifier, '/');
    }

    public function getPublicUrl(string $identifier): ?string
    {
        if (!$this->hasCapability(Capabilities::CAPABILITY_PUBLIC)) {
            return null;
        }
        $isFolder = str_ends_with($identifier, '/');
        $path = $this->getPath($this->canonicalizeAndCheckFileIdentifier($identifier));
        try {
            $publicUrl = $this->getFilesystem()->publicUrl($path);
        } catch (FilesystemException) {
            return null;
        }
        if ($isFolder && $path !== '') {
            $publicUrl .= '/';
        }
        return $publicUrl;
    }

    public function resolvePublicUrl(UriInterface $uri, string $sitePath): ?string
    {
        if (!$this->hasCapability(Capabilities::CAPABILITY_PUBLIC)
            || !$this->publicUrlGenerator instanceof PublicUrlResolverInterface
        ) {
            return null;
        }
        $path = $this->publicUrlGenerator->resolvePath($uri, $sitePath);
        return $path === null ? null : '/' . $path;
    }

    public function getRootLevelFolder(): string
    {
        return '/';
    }

    public function getDefaultFolder(): string
    {
        $identifier = '/user_upload/';
        if (!$this->folderExists($identifier)) {
            $identifier = $this->createFolder('user_upload');
        }
        return $identifier;
    }

    public function getRole(string $folderIdentifier): string
    {
        return $this->mappingFolderNameToRole[basename($folderIdentifier)] ?? FolderInterface::ROLE_DEFAULT;
    }

    public function createFolder(string $newFolderName, string $parentFolderIdentifier = '', bool $recursive = false): string
    {
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
        $newFolderName = trim($newFolderName, '/');
        if ($recursive) {
            $newFolderName = implode('/', array_map($this->sanitizeFileName(...), explode('/', $newFolderName)));
        } else {
            $newFolderName = $this->sanitizeFileName($newFolderName);
        }
        $newIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier . $newFolderName . '/');
        $this->getFilesystem()->createDirectory($this->getPath($newIdentifier));
        return $newIdentifier;
    }

    public function renameFolder(string $folderIdentifier, string $newName): array
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
        $newIdentifier = $this->canonicalizeAndCheckFolderIdentifier(
            $this->getParentFolderIdentifierOfIdentifier($folderIdentifier) . $this->sanitizeFileName($newName)
        );
        $this->forgetFolder($folderIdentifier);
        $this->forgetFolder($newIdentifier);
        return $this->transferFolder($folderIdentifier, $newIdentifier, true);
    }

    public function deleteFolder(string $folderIdentifier, bool $deleteRecursively = false): bool
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
        if (!$deleteRecursively && !$this->isFolderEmpty($folderIdentifier)) {
            throw new FileOperationErrorException(
                'Deleting folder "' . $folderIdentifier . '" failed, because it is not empty.',
                1792093590
            );
        }
        $this->forgetFolder($folderIdentifier);
        $this->getFilesystem()->deleteDirectory($this->getPath($folderIdentifier));
        return true;
    }

    public function fileExists(string $fileIdentifier): bool
    {
        return $this->getFilesystem()->fileExists($this->getPath($this->canonicalizeAndCheckFileIdentifier($fileIdentifier)));
    }

    public function folderExists(string $folderIdentifier): bool
    {
        $path = $this->getPath($this->canonicalizeAndCheckFolderIdentifier($folderIdentifier));
        return $path === '' || $this->getFilesystem()->directoryExists($path);
    }

    public function isFolderEmpty(string $folderIdentifier): bool
    {
        foreach ($this->listDirectoryEntries($folderIdentifier, true, true, false) as $ignored) {
            return false;
        }
        return true;
    }

    public function addFile(string $localFilePath, string $targetFolderIdentifier, string $newFileName = '', bool $removeOriginal = true): string
    {
        $localFilePath = $this->canonicalizeAndCheckFilePath($localFilePath);
        $newFileName = $this->sanitizeFileName($newFileName !== '' ? $newFileName : basename($localFilePath));
        $newFileIdentifier = $this->canonicalizeAndCheckFileIdentifier(
            $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier) . $newFileName
        );
        $this->writeLocalFile($localFilePath, $newFileIdentifier);
        if ($removeOriginal) {
            @unlink($localFilePath);
        }
        return $newFileIdentifier;
    }

    public function createFile(string $fileName, string $parentFolderIdentifier): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier(
            $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier) . $this->sanitizeFileName(ltrim($fileName, '/'))
        );
        // Writing replaces the contents of an existing file
        if ($this->fileExists($fileIdentifier)) {
            throw new ExistingTargetFileNameException(
                'The file "' . $fileIdentifier . '" already exists.',
                1792154301
            );
        }
        $this->getFilesystem()->write($this->getPath($fileIdentifier), '');
        return $fileIdentifier;
    }

    public function copyFileWithinStorage(string $fileIdentifier, string $targetFolderIdentifier, string $fileName): string
    {
        $newIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetFolderIdentifier . '/' . $fileName);
        $this->forgetFile($newIdentifier);
        $this->getFilesystem()->copy(
            $this->getPath($this->canonicalizeAndCheckFileIdentifier($fileIdentifier)),
            $this->getPath($newIdentifier),
            $this->copyOptions
        );
        return $newIdentifier;
    }

    public function renameFile(string $fileIdentifier, string $newName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $newIdentifier = $this->canonicalizeAndCheckFileIdentifier(
            $this->getParentFolderIdentifierOfIdentifier($fileIdentifier) . $this->sanitizeFileName($newName)
        );
        if ($this->fileExists($newIdentifier)) {
            throw new ExistingTargetFileNameException(
                'The target file "' . $newIdentifier . '" already exists.',
                1320291063
            );
        }
        $this->forgetFile($fileIdentifier);
        $this->getFilesystem()->move($this->getPath($fileIdentifier), $this->getPath($newIdentifier));
        return $newIdentifier;
    }

    public function replaceFile(string $fileIdentifier, string $localFilePath): bool
    {
        $this->writeLocalFile($localFilePath, $this->canonicalizeAndCheckFileIdentifier($fileIdentifier));
        @unlink($localFilePath);
        return true;
    }

    public function deleteFile(string $fileIdentifier): bool
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $this->forgetFile($fileIdentifier);
        $this->getFilesystem()->delete($this->getPath($fileIdentifier));
        return true;
    }

    /**
     * Adapters that provide the checksum with the requested algorithm spare
     * reading the file, all others stream it.
     */
    public function hash(string $fileIdentifier, string $hashAlgorithm): string
    {
        return $this->getFilesystem()->checksum(
            $this->getPath($this->canonicalizeAndCheckFileIdentifier($fileIdentifier)),
            ['checksum_algo' => $hashAlgorithm]
        );
    }

    public function moveFileWithinStorage(string $fileIdentifier, string $targetFolderIdentifier, string $newFileName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $targetIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetFolderIdentifier . '/' . $newFileName);
        $this->forgetFile($fileIdentifier);
        $this->forgetFile($targetIdentifier);
        $this->getFilesystem()->move($this->getPath($fileIdentifier), $this->getPath($targetIdentifier));
        return $targetIdentifier;
    }

    public function moveFolderWithinStorage(string $sourceFolderIdentifier, string $targetFolderIdentifier, string $newFolderName): array
    {
        $sourceFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($sourceFolderIdentifier);
        $targetFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);
        $this->forgetFolder($sourceFolderIdentifier);
        $this->forgetFolder($targetFolderIdentifier);
        return $this->transferFolder($sourceFolderIdentifier, $targetFolderIdentifier, true);
    }

    public function copyFolderWithinStorage(string $sourceFolderIdentifier, string $targetFolderIdentifier, string $newFolderName): bool
    {
        $targetFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);
        $this->forgetFolder($targetFolderIdentifier);
        $this->transferFolder($this->canonicalizeAndCheckFolderIdentifier($sourceFolderIdentifier), $targetFolderIdentifier, false);
        return true;
    }

    public function getFileContents(string $fileIdentifier): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        try {
            return $this->getFilesystem()->read($this->getPath($fileIdentifier));
        } catch (UnableToReadFile $e) {
            if ($this->fileExists($fileIdentifier)) {
                throw $e;
            }
            return '';
        }
    }

    public function setFileContents(string $fileIdentifier, string $contents): int
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $this->forgetFile($fileIdentifier);
        $this->getFilesystem()->write($this->getPath($fileIdentifier), $contents);
        return strlen($contents);
    }

    public function fileExistsInFolder(string $fileName, string $folderIdentifier): bool
    {
        return $this->fileExists($this->getFileInFolder($fileName, $folderIdentifier));
    }

    public function folderExistsInFolder(string $folderName, string $folderIdentifier): bool
    {
        return $this->folderExists($this->getFolderInFolder($folderName, $folderIdentifier));
    }

    /**
     * Files of a Flysystem adapter are not necessarily local, so a local copy
     * is always returned. Read-only copies are removed with the driver.
     */
    public function getFileForLocalProcessing(string $fileIdentifier, bool $writable = true): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        if (!$writable && isset($this->localCopies[$fileIdentifier]) && is_file($this->localCopies[$fileIdentifier])) {
            return $this->localCopies[$fileIdentifier];
        }
        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
        $source = $this->getFilesystem()->readStream($this->getPath($fileIdentifier));
        $target = fopen($temporaryPath, 'wb');
        if ($target === false) {
            fclose($source);
            throw new \RuntimeException(
                'Copying file "' . $fileIdentifier . '" to temporary path "' . $temporaryPath . '" failed.',
                1792093592
            );
        }
        $copiedBytes = stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);
        if ($copiedBytes === false || !$this->isCompleteLocalCopy($fileIdentifier, $copiedBytes)) {
            @unlink($temporaryPath);
            throw new \RuntimeException(
                'Copying file "' . $fileIdentifier . '" to temporary path "' . $temporaryPath . '" was incomplete.',
                1794516508
            );
        }
        @touch($temporaryPath, $this->getFileMetadata($fileIdentifier, 'mtime'));
        if (!$writable) {
            $this->localCopies[$fileIdentifier] = $temporaryPath;
        }
        return $temporaryPath;
    }

    /**
     * A copy with another size than the file is incomplete. The known size may
     * be outdated when the file was changed in the meantime, so it is requested
     * again before the copy is rejected. Adapters that do not know the size of
     * a file cannot be checked.
     */
    protected function isCompleteLocalCopy(string $fileIdentifier, int $copiedBytes): bool
    {
        try {
            if ($this->getFileMetadata($fileIdentifier, 'size') === $copiedBytes) {
                return true;
            }
            $this->forgetFile($fileIdentifier);
            return $this->getFileMetadata($fileIdentifier, 'size') === $copiedBytes;
        } catch (FilesystemException) {
            return true;
        }
    }

    /**
     * Flysystem only knows the visibility of a file, so read access is granted
     * for every existing file or folder and write access by the capability.
     * Entries known from a listing or an earlier request are not checked again.
     */
    public function getPermissions(string $identifier): array
    {
        $isFolder = str_ends_with($identifier, '/');
        $identifier = $isFolder ? $this->canonicalizeAndCheckFolderIdentifier($identifier) : $this->canonicalizeAndCheckFileIdentifier($identifier);
        if (!isset($this->fileMetadata[$identifier])) {
            if ($isFolder ? $this->folderExists($identifier) : $this->fileExists($identifier)) {
                $this->rememberFileMetadata($identifier, ['exists' => true]);
            } elseif ($isFolder || !$this->folderExists($identifier)) {
                throw new ResourcePermissionsUnavailableException('Error while fetching permissions for ' . $identifier, 1792093593);
            }
        }
        return $this->getPermissionsOfListedEntry($identifier);
    }

    /**
     * Returns the permissions of an entry known to exist from a listing
     *
     * @return array{r: bool, w: bool}
     */
    protected function getPermissionsOfListedEntry(string $identifier): array
    {
        return [
            'r' => true,
            'w' => $this->hasCapability(Capabilities::CAPABILITY_WRITABLE),
        ];
    }

    public function dumpFileContents(string $identifier): void
    {
        $stream = $this->getFilesystem()->readStream($this->getPath($this->canonicalizeAndCheckFileIdentifier($identifier)));
        fpassthru($stream);
        fclose($stream);
    }

    public function streamFile(string $identifier, array $properties): ResponseInterface
    {
        $identifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        $fileInfo = $this->getFileInfoByIdentifier($identifier, ['name', 'mimetype', 'mtime', 'size']);
        $downloadName = $properties['filename_overwrite'] ?? $fileInfo['name'] ?? '';
        $mimeType = $properties['mimetype_overwrite'] ?? $fileInfo['mimetype'] ?? '';
        $contentDisposition = ($properties['as_download'] ?? false) ? 'attachment' : 'inline';
        return new Response(
            new Stream($this->getFilesystem()->readStream($this->getPath($identifier))),
            200,
            [
                'Content-Disposition' => $contentDisposition . '; filename="' . $downloadName . '"',
                'Content-Type' => $mimeType,
                'Content-Length' => (string)$fileInfo['size'],
                'Last-Modified' => gmdate('D, d M Y H:i:s', $fileInfo['mtime']) . ' GMT',
            ]
        );
    }

    public function getFileInfoByIdentifier(string $fileIdentifier, array $propertiesToExtract = []): array
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        if (!isset($this->fileMetadata[$fileIdentifier])) {
            if (!$this->fileExists($fileIdentifier)) {
                throw new FileDoesNotExistException('File ' . $fileIdentifier . ' does not exist.', 1792093594);
            }
            $this->rememberFileMetadata($fileIdentifier, ['exists' => true]);
        }
        if ($propertiesToExtract === []) {
            $propertiesToExtract = [
                'size', 'atime', 'mtime', 'ctime', 'mimetype', 'name', 'extension',
                'identifier', 'identifier_hash', 'storage', 'folder_hash',
            ];
        }
        $fileInformation = [];
        foreach ($propertiesToExtract as $property) {
            $fileInformation[$property] = $this->getFileInformationProperty($fileIdentifier, $property);
        }
        return $fileInformation;
    }

    /**
     * Flysystem knows only the modification time, so it is used for the access
     * and creation time as well.
     */
    protected function getFileInformationProperty(string $fileIdentifier, string $property): bool|int|string|null
    {
        return match ($property) {
            'size' => $this->getFileMetadata($fileIdentifier, 'size'),
            'atime', 'mtime', 'ctime' => $this->getFileMetadata($fileIdentifier, 'mtime'),
            'mimetype' => $this->getFileMetadata($fileIdentifier, 'mimetype'),
            'name' => basename($fileIdentifier),
            'extension' => pathinfo($fileIdentifier, PATHINFO_EXTENSION),
            'identifier' => $fileIdentifier,
            'storage' => $this->storageUid,
            'identifier_hash' => $this->hashIdentifier($fileIdentifier),
            'folder_hash' => $this->hashIdentifier($this->getParentFolderIdentifierOfIdentifier($fileIdentifier)),
            default => throw new \InvalidArgumentException(
                sprintf('The information "%s" is not available.', $property),
                1792093595
            ),
        };
    }

    /**
     * @param 'size'|'mtime'|'mimetype' $property
     */
    protected function getFileMetadata(string $fileIdentifier, string $property): int|string
    {
        if (!isset($this->fileMetadata[$fileIdentifier][$property])) {
            $path = $this->getPath($fileIdentifier);
            $this->rememberFileMetadata($fileIdentifier, [$property => match ($property) {
                'size' => $this->getFilesystem()->fileSize($path),
                'mtime' => $this->getFilesystem()->lastModified($path),
                'mimetype' => $this->getMimeType($path, $this->fileMetadata[$fileIdentifier]['reportedMimetype'] ?? null),
            }]);
        }
        return $this->fileMetadata[$fileIdentifier][$property];
    }

    /**
     * @param array{exists?: true, size?: int|null, mtime?: int|null, mimetype?: string|null, reportedMimetype?: string|null} $metadata
     */
    protected function rememberFileMetadata(string $fileIdentifier, array $metadata): void
    {
        if (!isset($this->fileMetadata[$fileIdentifier]) && count($this->fileMetadata) >= static::FILE_METADATA_LIMIT) {
            $this->fileMetadata = [];
        }
        $this->fileMetadata[$fileIdentifier] = array_filter($metadata, static fn(mixed $value): bool => $value !== null)
            + ($this->fileMetadata[$fileIdentifier] ?? []);
    }

    /**
     * Discards what is known about a file that is changed
     */
    protected function forgetFile(string $fileIdentifier): void
    {
        unset($this->fileMetadata[$fileIdentifier]);
        if (isset($this->localCopies[$fileIdentifier])) {
            @unlink($this->localCopies[$fileIdentifier]);
            unset($this->localCopies[$fileIdentifier]);
        }
    }

    protected function forgetFolder(string $folderIdentifier): void
    {
        foreach (array_keys($this->fileMetadata + $this->localCopies) as $fileIdentifier) {
            if (str_starts_with((string)$fileIdentifier, $folderIdentifier)) {
                $this->forgetFile((string)$fileIdentifier);
            }
        }
    }

    /**
     * Follows the rules of FileInfo::getMimeType() for local files: a MIME type
     * configured for the file extension wins over the one of the adapter, and
     * the registered MIME type guessers refine the result.
     */
    protected function getMimeType(string $path, ?string $reportedMimeType = null): string
    {
        $configuredMimeType = $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'][strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
        if (!empty($configuredMimeType)) {
            $mimeType = (string)$configuredMimeType;
        } else {
            try {
                $mimeType = $reportedMimeType ?? $this->getFilesystem()->mimeType($path);
            } catch (FilesystemException) {
                $mimeType = false;
            }
        }
        $fileInfo = new FileInfo($path);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FileInfo::class]['mimeTypeGuessers'] ?? [] as $mimeTypeGuesser) {
            $hookParameters = [
                'mimeType' => &$mimeType,
                'targetFileName' => null,
            ];
            GeneralUtility::callUserFunction($mimeTypeGuesser, $hookParameters, $fileInfo);
        }
        return is_string($mimeType) && $mimeType !== '' ? $mimeType : 'application/octet-stream';
    }

    public function getFolderInfoByIdentifier(string $folderIdentifier): array
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
        if (!isset($this->fileMetadata[$folderIdentifier])) {
            if (!$this->folderExists($folderIdentifier)) {
                throw new FolderDoesNotExistException('Folder "' . $folderIdentifier . '" does not exist.', 1792093596);
            }
            $this->rememberFileMetadata($folderIdentifier, ['exists' => true]);
        }
        // Only a listing knows when a folder was modified, object storages have no such information at all
        $lastModified = $this->fileMetadata[$folderIdentifier]['mtime'] ?? 0;
        return [
            'identifier' => $folderIdentifier,
            'name' => basename($folderIdentifier),
            'mtime' => $lastModified,
            'ctime' => $lastModified,
            'storage' => $this->storageUid,
        ];
    }

    public function getFileInFolder(string $fileName, string $folderIdentifier): string
    {
        return $this->canonicalizeAndCheckFileIdentifier($folderIdentifier . '/' . $fileName);
    }

    public function getFilesInFolder(string $folderIdentifier, int $start = 0, int $numberOfItems = 0, bool $recursive = false, array $filenameFilterCallbacks = [], string $sort = '', bool $sortRev = false): array
    {
        return $this->getDirectoryItemList($folderIdentifier, $start, $numberOfItems, $filenameFilterCallbacks, true, false, $recursive, $sort, $sortRev);
    }

    public function getFolderInFolder(string $folderName, string $folderIdentifier): string
    {
        return $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier . '/' . $folderName);
    }

    public function getFoldersInFolder(string $folderIdentifier, int $start = 0, int $numberOfItems = 0, bool $recursive = false, array $folderNameFilterCallbacks = [], string $sort = '', bool $sortRev = false): array
    {
        return $this->getDirectoryItemList($folderIdentifier, $start, $numberOfItems, $folderNameFilterCallbacks, false, true, $recursive, $sort, $sortRev);
    }

    /**
     * Counts while iterating the listing, without sorting or keeping it.
     */
    public function countFilesInFolder(string $folderIdentifier, bool $recursive = false, array $filenameFilterCallbacks = []): int
    {
        return $this->countDirectoryItems($folderIdentifier, $filenameFilterCallbacks, true, false, $recursive);
    }

    public function countFoldersInFolder(string $folderIdentifier, bool $recursive = false, array $folderNameFilterCallbacks = []): int
    {
        return $this->countDirectoryItems($folderIdentifier, $folderNameFilterCallbacks, false, true, $recursive);
    }

    /**
     * @return array<string|int, string> identifiers as keys and values
     */
    protected function getDirectoryItemList(string $folderIdentifier, int $start, int $numberOfItems, array $filterMethods, bool $includeFiles = true, bool $includeDirs = true, bool $recursive = false, string $sort = '', bool $sortRev = false): array
    {
        $entries = $this->sortDirectoryEntries(
            iterator_to_array($this->listDirectoryEntries($folderIdentifier, $includeFiles, $includeDirs, $recursive)),
            $sort,
            $sortRev
        );
        $items = [];
        foreach ($entries as $identifier => $attributes) {
            if ($numberOfItems > 0 && count($items) >= $numberOfItems) {
                break;
            }
            if (!$this->isDirectoryItemIncluded($filterMethods, (string)$identifier)) {
                continue;
            }
            if ($start > 0) {
                $start--;
                continue;
            }
            $items[$identifier] = (string)$identifier;
        }
        return $items;
    }

    protected function countDirectoryItems(string $folderIdentifier, array $filterMethods, bool $includeFiles, bool $includeDirs, bool $recursive): int
    {
        $count = 0;
        foreach ($this->listDirectoryEntries($folderIdentifier, $includeFiles, $includeDirs, $recursive) as $identifier => $attributes) {
            if ($this->isDirectoryItemIncluded($filterMethods, $identifier)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return \Generator<string, StorageAttributes> identifier => attributes
     */
    protected function listDirectoryEntries(string $folderIdentifier, bool $includeFiles, bool $includeDirs, bool $recursive): \Generator
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
        if (!$this->folderExists($folderIdentifier)) {
            throw new \InvalidArgumentException(
                'Cannot list items in directory ' . $folderIdentifier . ' - does not exist or is no directory',
                1792093597
            );
        }
        foreach ($this->getFilesystem()->listContents($this->getPath($folderIdentifier), $recursive) as $attributes) {
            $identifier = '/' . $attributes->path() . ($attributes->isDir() ? '/' : '');
            $this->rememberFileMetadata($identifier, [
                'exists' => true,
                'size' => $attributes instanceof FileAttributes ? $attributes->fileSize() : null,
                'mtime' => $attributes->lastModified(),
                'reportedMimetype' => $attributes instanceof FileAttributes ? $attributes->mimeType() : null,
            ]);
            if ($attributes->isDir() ? !$includeDirs : !$includeFiles) {
                continue;
            }
            yield $identifier => $attributes;
        }
    }

    protected function isDirectoryItemIncluded(array $filterMethods, string $identifier): bool
    {
        try {
            return $this->applyFilterMethodsToDirectoryItem(
                $filterMethods,
                basename($identifier),
                $identifier,
                $this->getParentFolderIdentifierOfIdentifier($identifier)
            );
        } catch (InvalidPathException) {
            return false;
        }
    }

    /**
     * Sorts by name, unless another supported property is given. Entries
     * with the same sorting key are sorted by their identifier.
     *
     * @param array<string, StorageAttributes> $entries
     * @return array<string, StorageAttributes>
     */
    protected function sortDirectoryEntries(array $entries, string $sort, bool $sortRev): array
    {
        $identifiersBySortingKey = [];
        foreach ($entries as $identifier => $attributes) {
            $identifier = (string)$identifier;
            $isFile = $attributes instanceof FileAttributes;
            $sortingKey = match ($sort) {
                'size' => ($isFile ? $attributes->fileSize() ?? $this->getFileMetadata($identifier, 'size') : 0) . 's',
                'tstamp' => ($isFile ? $attributes->lastModified() ?? $this->getFileMetadata($identifier, 'mtime') : $attributes->lastModified()) . 't',
                'crdate' => ($isFile ? $this->getFileInformationProperty($identifier, 'ctime') : $attributes->lastModified()) . 'c',
                'rw' => (static fn(array $permissions): string => ($permissions['r'] ? 'R' : '') . ($permissions['w'] ? 'W' : ''))($this->getPermissionsOfListedEntry($identifier)),
                'fileext' => pathinfo($identifier, PATHINFO_EXTENSION),
                default => basename($identifier),
            };
            $identifiersBySortingKey[$sortingKey . "\0" . $identifier] = $identifier;
        }
        if ($sortRev) {
            krsort($identifiersBySortingKey, SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            ksort($identifiersBySortingKey, SORT_NATURAL | SORT_FLAG_CASE);
        }
        $sortedEntries = [];
        foreach ($identifiersBySortingKey as $identifier) {
            $sortedEntries[$identifier] = $entries[$identifier];
        }
        return $sortedEntries;
    }

    /**
     * Copies or moves a folder with all its contents, Flysystem only copies
     * and moves single files.
     *
     * @return array<non-empty-string, non-empty-string> map of old to new identifiers
     */
    protected function transferFolder(string $sourceFolderIdentifier, string $targetFolderIdentifier, bool $move): array
    {
        $filesystem = $this->getFilesystem();
        $identifierMap = [$sourceFolderIdentifier => $targetFolderIdentifier];
        $entries = iterator_to_array($this->listDirectoryEntries($sourceFolderIdentifier, true, true, true));
        // Parent folders are created before their content
        ksort($entries);
        $filesystem->createDirectory($this->getPath($targetFolderIdentifier));
        foreach ($entries as $identifier => $attributes) {
            $newIdentifier = $targetFolderIdentifier . substr($identifier, strlen($sourceFolderIdentifier));
            if ($attributes->isDir()) {
                $filesystem->createDirectory($this->getPath($newIdentifier));
            } elseif ($move) {
                $filesystem->move($this->getPath($identifier), $this->getPath($newIdentifier));
            } else {
                $filesystem->copy($this->getPath($identifier), $this->getPath($newIdentifier), $this->copyOptions);
            }
            $identifierMap[$identifier] = $newIdentifier;
        }
        if ($move) {
            $filesystem->deleteDirectory($this->getPath($sourceFolderIdentifier));
            $this->forgetFolder($sourceFolderIdentifier);
        }
        return $identifierMap;
    }

    protected function writeLocalFile(string $localFilePath, string $fileIdentifier): void
    {
        $stream = @fopen($localFilePath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Reading local file ' . $localFilePath . ' for ' . $fileIdentifier . ' failed.', 1792093598);
        }
        $this->forgetFile($fileIdentifier);
        try {
            $this->getFilesystem()->writeStream($this->getPath($fileIdentifier), $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
