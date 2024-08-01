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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\SelfEmittableLazyOpenStream;
use TYPO3\CMS\Core\Resource\Capabilities;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\Exception\ResourcePermissionsUnavailableException;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * A concrete implementation for a File Driver for the local file system.
 */
class LocalDriver extends AbstractHierarchicalFilesystemDriver implements StreamableDriverInterface
{
    /**
     * @var string
     */
    public const UNSAFE_FILENAME_CHARACTER_EXPRESSION = '\\x00-\\x2C\\/\\x3A-\\x3F\\x5B-\\x60\\x7B-\\xBF';

    /**
     * The absolute base path. It always contains a trailing slash.
     */
    protected string $absoluteBasePath = '/';

    /**
     * A list of all supported hash algorithms, written all lower case.
     */
    protected array $supportedHashAlgorithms = ['sha1', 'md5'];

    /**
     * The base URL that points to this driver's storage. As long is this
     * is not set, it is assumed that this folder is not publicly available
     */
    protected ?string $baseUri = null;

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
        // The capabilities default of this driver. See Capabilities::CAPABILITY_* constants for possible values
        $this->capabilities = new Capabilities(
            Capabilities::CAPABILITY_BROWSABLE
            | Capabilities::CAPABILITY_PUBLIC
            | Capabilities::CAPABILITY_WRITABLE
            | Capabilities::CAPABILITY_HIERARCHICAL_IDENTIFIERS
        );
    }

    /**
     * Merges the capabilities from the user of the storage configuration into the actual
     * capabilities of the driver and returns the result.
     */
    public function mergeConfigurationCapabilities(Capabilities $capabilities): Capabilities
    {
        $this->capabilities->and($capabilities);
        return $this->capabilities;
    }

    public function processConfiguration(): void
    {
        try {
            $this->absoluteBasePath = $this->calculateBasePath($this->configuration);
        } catch (InvalidConfigurationException $e) {
            // The storage is offline, but the absolute base path requires a "/" at the end.
            $this->absoluteBasePath = '/';
            throw $e;
        }
        $this->determineBaseUrl();
        if ($this->baseUri === null) {
            // remove public flag
            $this->capabilities->removeCapability(Capabilities::CAPABILITY_PUBLIC);
        }
    }

    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     */
    public function initialize(): void {}

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
     * Calculates the absolute path to this driver's storage location
     */
    protected function calculateBasePath(array $configuration): string
    {
        if (!array_key_exists('basePath', $configuration) || empty($configuration['basePath'])) {
            throw new InvalidConfigurationException(
                'Configuration must contain base path.',
                1346510477
            );
        }

        if (!empty($configuration['pathType']) && $configuration['pathType'] === 'relative') {
            $relativeBasePath = $configuration['basePath'];
            $absoluteBasePath = Environment::getPublicPath() . '/' . $relativeBasePath;
        } else {
            $absoluteBasePath = $configuration['basePath'];
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
     * Returns a publicly accessible URL to a file or folder.
     * For the local driver, this will always return a path relative to public web path.
     * For non-public storages, this method returns null.
     *
     * @param non-empty-string $identifier
     * @return string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl(string $identifier): ?string
    {
        $publicUrl = null;
        if ($this->baseUri !== null) {
            $uriParts = explode('/', ltrim($identifier, '/'));
            $uriParts = array_map(rawurlencode(...), $uriParts);
            $identifier = implode('/', $uriParts);
            $publicUrl = $this->baseUri . $identifier;
        }
        return $publicUrl;
    }

    /**
     * Returns the identifier of the root level folder of the storage.
     *
     * @return non-empty-string
     */
    public function getRootLevelFolder(): string
    {
        return '/';
    }

    /**
     * Returns the identifier of the default folder where new files should be put into.
     *
     * @return non-empty-string
     */
    public function getDefaultFolder(): string
    {
        $identifier = '/user_upload/';
        $createFolder = !$this->folderExists($identifier);
        if ($createFolder === true) {
            $identifier = $this->createFolder('user_upload');
        }
        return $identifier;
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
            throw new \InvalidArgumentException('File ' . $fileIdentifier . ' does not exist.', 1314516809);
        }

        $dirPath = PathUtility::dirname($fileIdentifier);
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
            'name' => PathUtility::basename($folderIdentifier),
            'mtime' => filemtime($absolutePath),
            'ctime' => filectime($absolutePath),
            'storage' => $this->storageUid,
        ];
    }

    /**
     * Returns a string where any character not matching [.a-zA-Z0-9_-] is
     * substituted by '_'
     * Trailing dots are removed
     *
     * Previously in \TYPO3\CMS\Core\Utility\File\BasicFileUtility::cleanFileName()
     *
     * @param string $fileName Input string, typically the body of a fileName
     * @param non-empty-string $charset Charset of the a fileName (defaults to utf-8)
     * @return non-empty-string Output string with any characters not matching [.a-zA-Z0-9_-] is substituted by '_' and trailing dots removed
     * @todo: at some point it is safe to drop the second argument $charset
     */
    public function sanitizeFileName(string $fileName, string $charset = 'utf-8'): string
    {
        if ($charset === 'utf-8') {
            $fileName = \Normalizer::normalize($fileName) ?: $fileName;
        }

        // Handle UTF-8 characters
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
            // Allow ".", "-", 0-9, a-z, A-Z and everything beyond U+C0 (latin capital letter a with grave)
            $cleanFileName = (string)preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . ']/u', '_', trim($fileName));
        } else {
            $fileName = GeneralUtility::makeInstance(CharsetConverter::class)->specCharsToASCII($charset, $fileName);
            // Replace unwanted characters with underscores
            $cleanFileName = (string)preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . '\\xC0-\\xFF]/', '_', trim($fileName));
        }
        // Strip trailing dots and return
        $cleanFileName = rtrim($cleanFileName, '.');
        if ($cleanFileName === '') {
            throw new InvalidFileNameException(
                'File name ' . $fileName . ' is invalid.',
                1320288991
            );
        }
        return $cleanFileName;
    }

    /**
     * Generic wrapper for extracting a list of items from a path.
     *
     * @param int $start The position to start the listing; if not set, start from the beginning
     * @param int $numberOfItems The number of items to list; if set to zero, all items are returned
     * @param array $filterMethods The filter methods used to filter the directory items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array<string|int, string> folder identifiers (where key and value are identical, but int-like identifiers
     *         will get converted to int array keys)
     */
    protected function getDirectoryItemList(string $folderIdentifier, int $start, int $numberOfItems, array $filterMethods, bool $includeFiles = true, bool $includeDirs = true, bool $recursive = false, string $sort = '', bool $sortRev = false): array
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
        $realPath = $this->getAbsolutePath($folderIdentifier);
        if (!is_dir($realPath)) {
            throw new \InvalidArgumentException(
                'Cannot list items in directory ' . $folderIdentifier . ' - does not exist or is no directory',
                1314349666
            );
        }

        $items = $this->retrieveFileAndFoldersInPath($realPath, $recursive, $includeFiles, $includeDirs, $sort, $sortRev);
        $iterator = new \ArrayIterator($items);
        if ($iterator->count() === 0) {
            return [];
        }

        // $c is the counter for how many items we still have to fetch (-1 is unlimited)
        $c = $numberOfItems > 0 ? $numberOfItems : -1;
        $items = [];
        while ($iterator->valid() && ($numberOfItems === 0 || $c > 0)) {
            // $iteratorItem is the file or folder name
            $iteratorItem = $iterator->current();
            // go on to the next iterator item now as we might skip this one early
            $iterator->next();

            try {
                if (
                    !$this->applyFilterMethodsToDirectoryItem(
                        $filterMethods,
                        $iteratorItem['name'],
                        $iteratorItem['identifier'],
                        $this->getParentFolderIdentifierOfIdentifier($iteratorItem['identifier'])
                    )
                ) {
                    continue;
                }
                if ($start > 0) {
                    $start--;
                } else {
                    // The identifier can also be an int-like string, resulting in int array keys.
                    $items[$iteratorItem['identifier']] = $iteratorItem['identifier'];
                    // Decrement item counter to make sure we only return $numberOfItems
                    // we cannot do this earlier in the method (unlike moving the iterator forward) because we only add the
                    // item here
                    --$c;
                }
            } catch (InvalidPathException) {
            }
        }
        return $items;
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
     * Returns a file inside the specified path.
     *
     * @param non-empty-string $fileName
     * @param non-empty-string $folderIdentifier
     * @return non-empty-string File Identifier
     */
    public function getFileInFolder(string $fileName, string $folderIdentifier): string
    {
        return $this->canonicalizeAndCheckFileIdentifier($folderIdentifier . '/' . $fileName);
    }

    /**
     * Returns a list of files inside the specified path.
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $filenameFilterCallbacks The method callbacks to use for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return string[] of FileIdentifiers
     */
    public function getFilesInFolder(string $folderIdentifier, int $start = 0, int $numberOfItems = 0, bool $recursive = false, array $filenameFilterCallbacks = [], string $sort = '', bool $sortRev = false): array
    {
        return $this->getDirectoryItemList($folderIdentifier, $start, $numberOfItems, $filenameFilterCallbacks, true, false, $recursive, $sort, $sortRev);
    }

    /**
     * Returns the number of files inside the specified path.
     *
     * @param non-empty-string $folderIdentifier
     * @param list<callable> $filenameFilterCallbacks callbacks for filtering the items
     * @return int<0, max> Number of files in folder
     */
    public function countFilesInFolder(string $folderIdentifier, bool $recursive = false, array $filenameFilterCallbacks = []): int
    {
        return count($this->getFilesInFolder($folderIdentifier, 0, 0, $recursive, $filenameFilterCallbacks));
    }

    /**
     * Returns a list of folders inside the specified path.
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks The method callbacks to use for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array<string|int, string> folder identifiers (where key and value are identical, but int-like identifiers
     *         will get converted to int array keys)
     */
    public function getFoldersInFolder(string $folderIdentifier, int $start = 0, int $numberOfItems = 0, bool $recursive = false, array $folderNameFilterCallbacks = [], string $sort = '', bool $sortRev = false): array
    {
        return $this->getDirectoryItemList($folderIdentifier, $start, $numberOfItems, $folderNameFilterCallbacks, false, true, $recursive, $sort, $sortRev);
    }

    /**
     * Returns the number of folders inside the specified path.
     *
     * @param non-empty-string $folderIdentifier
     * @param list<callable> $folderNameFilterCallbacks callbacks for filtering the items
     * @return int<0, max> Number of folders in folder
     */
    public function countFoldersInFolder(string $folderIdentifier, bool $recursive = false, array $folderNameFilterCallbacks = []): int
    {
        return count($this->getFoldersInFolder($folderIdentifier, 0, 0, $recursive, $folderNameFilterCallbacks));
    }

    /**
     * Returns a list with the names of all files and folders in a path, optionally recursive.
     *
     * @param string $path The absolute path
     * @param bool $recursive If TRUE, recursively fetches files and folders
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     */
    protected function retrieveFileAndFoldersInPath(string $path, bool $recursive = false, bool $includeFiles = true, bool $includeDirs = true, string $sort = '', bool $sortRev = false): array
    {
        $pathLength = strlen($this->getAbsoluteBasePath());
        $iteratorMode = \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::FOLLOW_SYMLINKS;
        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, $iteratorMode),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );
        } else {
            $iterator = new \RecursiveDirectoryIterator($path, $iteratorMode);
        }

        $directoryEntries = [];
        while ($iterator->valid()) {
            /** @var \SplFileInfo $entry */
            $entry = $iterator->current();
            $isFile = $entry->isFile();
            $isDirectory = !$isFile && $entry->isDir();
            if (
                (!$isFile && !$isDirectory) // skip non-files/non-folders
                || ($isFile && !$includeFiles) // skip files if they are excluded
                || ($isDirectory && !$includeDirs) // skip directories if they are excluded
                || $entry->getFilename() === '' // skip empty entries
                || !$entry->isReadable() // skip unreadable entries
            ) {
                $iterator->next();
                continue;
            }
            $entryIdentifier = '/' . substr($entry->getPathname(), $pathLength);
            $entryName = PathUtility::basename($entryIdentifier);
            if ($isDirectory) {
                $entryIdentifier .= '/';
            }
            $entryArray = [
                'identifier' => $entryIdentifier,
                'name' => $entryName,
                'type' => $isDirectory ? 'dir' : 'file',
            ];
            $directoryEntries[$entryIdentifier] = $entryArray;
            $iterator->next();
        }
        return $this->sortDirectoryEntries($directoryEntries, $sort, $sortRev);
    }

    /**
     * Sort the directory entries by a certain key.
     *
     * @param array $directoryEntries Array of directory entry arrays from retrieveFileAndFoldersInPath()
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array Sorted entries. Content of the keys is undefined.
     */
    protected function sortDirectoryEntries(array $directoryEntries, string $sort = '', bool $sortRev = false): array
    {
        $entriesToSort = [];
        foreach ($directoryEntries as $entryArray) {
            $dir      = pathinfo($entryArray['name'], PATHINFO_DIRNAME) . '/';
            $fullPath = $this->getAbsoluteBasePath() . $entryArray['identifier'];
            switch ($sort) {
                case 'size':
                    $sortingKey = '0';
                    if ($entryArray['type'] === 'file') {
                        $sortingKey = $this->getSpecificFileInformation($fullPath, $dir, 'size');
                    }
                    // Add a character for a natural order sorting
                    $sortingKey .= 's';
                    break;
                case 'rw':
                    $perms = $this->getPermissions($entryArray['identifier']);
                    $sortingKey = ($perms['r'] ? 'R' : '')
                        . ($perms['w'] ? 'W' : '');
                    break;
                case 'fileext':
                    $sortingKey = pathinfo($entryArray['name'], PATHINFO_EXTENSION);
                    break;
                case 'tstamp':
                    $sortingKey = $this->getSpecificFileInformation($fullPath, $dir, 'mtime');
                    // Add a character for a natural order sorting
                    $sortingKey .= 't';
                    break;
                case 'crdate':
                    $sortingKey = $this->getSpecificFileInformation($fullPath, $dir, 'ctime');
                    // Add a character for a natural order sorting
                    $sortingKey .= 'c';
                    break;
                case 'name':
                case 'file':
                default:
                    $sortingKey = $entryArray['name'];
            }
            $i = 0;
            while (isset($entriesToSort[$sortingKey . $i])) {
                $i++;
            }
            $entriesToSort[$sortingKey . $i] = $entryArray;
        }
        uksort($entriesToSort, 'strnatcasecmp');

        if ($sortRev) {
            $entriesToSort = array_reverse($entriesToSort);
        }

        return $entriesToSort;
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
        $identifier = $this->canonicalizeAndCheckFileIdentifier($containerPath . PathUtility::basename($fileIdentifier));

        $fileInfo = GeneralUtility::makeInstance(FileInfo::class, $fileIdentifier);
        return match ($property) {
            'size' => $fileInfo->getSize(),
            'atime' => $fileInfo->getATime(),
            'mtime' => $fileInfo->getMTime(),
            'ctime' => $fileInfo->getCTime(),
            'name' => PathUtility::basename($fileIdentifier),
            'extension' => PathUtility::pathinfo($fileIdentifier, PATHINFO_EXTENSION),
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
     * Creates a (cryptographic) hash for a file.
     *
     * @param string $hashAlgorithm The hash algorithm to use
     */
    public function hash(string $fileIdentifier, string $hashAlgorithm): string
    {
        if (!in_array($hashAlgorithm, $this->supportedHashAlgorithms, true)) {
            throw new \InvalidArgumentException('Hash algorithm "' . $hashAlgorithm . '" is not supported.', 1304964032);
        }
        return match ($hashAlgorithm) {
            'sha1' => sha1_file($this->getAbsolutePath($fileIdentifier)),
            'md5' => md5_file($this->getAbsolutePath($fileIdentifier)),
            default => throw new \RuntimeException('Hash algorithm ' . $hashAlgorithm . ' is not implemented.', 1329644451),
        };
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
        // as for the "virtual storage" for backwards-compatibility, this check always fails, as the file probably lies under public web path
        // thus, it is not checked here
        // @todo is check in storage
        if (str_starts_with($localFilePath, $this->absoluteBasePath) && $this->storageUid > 0) {
            throw new \InvalidArgumentException('Cannot add a file that is already part of this storage.', 1314778269);
        }
        $newFileName = $this->sanitizeFileName($newFileName !== '' ? $newFileName : PathUtility::basename($localFilePath));
        $newFileIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier) . $newFileName;
        $targetPath = $this->getAbsolutePath($newFileIdentifier);

        if ($removeOriginal) {
            if (is_uploaded_file($localFilePath)) {
                $result = move_uploaded_file($localFilePath, $targetPath);
            } else {
                $result = rename($localFilePath, $targetPath);
            }
        } else {
            $result = copy($localFilePath, $targetPath);
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
     * Checks if a file exists on the file system.
     *
     * @param non-empty-string $fileIdentifier
     */
    public function fileExists(string $fileIdentifier): bool
    {
        $absoluteFilePath = $this->getAbsolutePath($fileIdentifier);
        return is_file($absoluteFilePath);
    }

    /**
     * Checks if a file inside a folder exists.
     *
     * @param non-empty-string $fileName
     * @param non-empty-string $folderIdentifier
     */
    public function fileExistsInFolder(string $fileName, string $folderIdentifier): bool
    {
        $identifier = $folderIdentifier . '/' . $fileName;
        $identifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        return $this->fileExists($identifier);
    }

    /**
     * Checks if a folder exists.
     *
     * @param non-empty-string $folderIdentifier
     */
    public function folderExists(string $folderIdentifier): bool
    {
        $absoluteFilePath = $this->getAbsolutePath($folderIdentifier);
        return is_dir($absoluteFilePath);
    }

    /**
     * Checks if a folder inside a folder exists.
     *
     * @param non-empty-string $folderName
     * @param non-empty-string $folderIdentifier
     */
    public function folderExistsInFolder(string $folderName, string $folderIdentifier): bool
    {
        $identifier = $folderIdentifier . '/' . $folderName;
        $identifier = $this->canonicalizeAndCheckFolderIdentifier($identifier);
        return $this->folderExists($identifier);
    }

    /**
     * Returns the identifier for a folder within a given folder.
     *
     * @param non-empty-string $folderName The name of the target folder
     * @param non-empty-string $folderIdentifier
     * @return non-empty-string
     */
    public function getFolderInFolder(string $folderName, string $folderIdentifier): string
    {
        return $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier . '/' . $folderName);
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
            $result = move_uploaded_file($localFilePath, $filePath);
        } else {
            $result = rename($localFilePath, $filePath);
        }
        GeneralUtility::fixPermissions($filePath);
        if ($result === false) {
            throw new \RuntimeException('Replacing file ' . $fileIdentifier . ' with ' . $localFilePath . ' failed.', 1315314711);
        }
        return true;
    }

    /**
     * Copies a file *within* the current storage.
     * The responsibility of this method in the Driver is only about an intra-storage copy action,
     * where a file is just copied to another folder in the same storage.
     *
     * @param non-empty-string $fileIdentifier
     * @param non-empty-string $targetFolderIdentifier
     * @param non-empty-string $fileName
     * @return non-empty-string the identifier of the new file
     */
    public function copyFileWithinStorage(string $fileIdentifier, string $targetFolderIdentifier, string $fileName): string
    {
        $sourcePath = $this->getAbsolutePath($fileIdentifier);
        $newIdentifier = $targetFolderIdentifier . '/' . $fileName;
        $newIdentifier = $this->canonicalizeAndCheckFileIdentifier($newIdentifier);

        $absoluteFilePath = $this->getAbsolutePath($newIdentifier);
        copy($sourcePath, $absoluteFilePath);
        GeneralUtility::fixPermissions($absoluteFilePath);
        return $newIdentifier;
    }

    /**
     * Moves a file *within* the current storage.
     * The responsibility of this method in the Driver is only about an intra-storage move action,
     * where a file is just moved to another folder in the same storage.
     *
     * @param non-empty-string $fileIdentifier
     * @param non-empty-string $targetFolderIdentifier
     * @param non-empty-string $newFileName
     * @return non-empty-string
     */
    public function moveFileWithinStorage(string $fileIdentifier, string $targetFolderIdentifier, string $newFileName): string
    {
        $sourcePath = $this->getAbsolutePath($fileIdentifier);
        $targetIdentifier = $targetFolderIdentifier . '/' . $newFileName;
        $targetIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetIdentifier);
        $result = rename($sourcePath, $this->getAbsolutePath($targetIdentifier));
        if ($result === false) {
            throw new \RuntimeException('Moving file ' . $sourcePath . ' to ' . $targetIdentifier . ' failed.', 1315314712);
        }
        return $targetIdentifier;
    }

    /**
     * Copies a file to a temporary path and returns that path.
     */
    protected function copyFileToTemporaryPath(string $fileIdentifier): string
    {
        $sourcePath = $this->getAbsolutePath($fileIdentifier);
        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
        $result = copy($sourcePath, $temporaryPath);
        touch($temporaryPath, (int)filemtime($sourcePath));
        if ($result === false) {
            throw new \RuntimeException(
                'Copying file "' . $fileIdentifier . '" to temporary path "' . $temporaryPath . '" failed.',
                1320577649
            );
        }
        return $temporaryPath;
    }

    /**
     * Moves a file or folder to the given directory, renaming the source in the process if
     * a file or folder of the same name already exists in the target path.
     */
    protected function recycleFileOrFolder(string $filePath, string $recycleDirectory): bool
    {
        $destinationFile = $recycleDirectory . '/' . PathUtility::basename($filePath);
        if (file_exists($destinationFile)) {
            $timeStamp = \DateTimeImmutable::createFromFormat('U.u', (string)microtime(true))->format('YmdHisu');
            $destinationFile = $recycleDirectory . '/' . $timeStamp . '_' . PathUtility::basename($filePath);
        }
        $result = rename($filePath, $destinationFile);
        // Update the mtime for the file, so the recycler garbage collection task knows which files to delete
        // Using ctime() is not possible there since this is not supported on Windows
        if ($result) {
            touch($destinationFile);
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
     * Folder equivalent to moveFileWithinStorage().
     *
     * @param non-empty-string $sourceFolderIdentifier
     * @param non-empty-string $targetFolderIdentifier
     * @param non-empty-string $newFolderName
     * @return array<non-empty-string, non-empty-string> A map of old to new file identifiers
     */
    public function moveFolderWithinStorage(string $sourceFolderIdentifier, string $targetFolderIdentifier, string $newFolderName): array
    {
        $sourcePath = $this->getAbsolutePath($sourceFolderIdentifier);
        $relativeTargetPath = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);
        $targetPath = $this->getAbsolutePath($relativeTargetPath);
        // get all files and folders we are going to move, to have a map for updating later.
        $filesAndFolders = $this->retrieveFileAndFoldersInPath($sourcePath, true);
        $result = rename($sourcePath, $targetPath);
        if ($result === false) {
            throw new \RuntimeException('Moving folder ' . $sourcePath . ' to ' . $targetPath . ' failed.', 1320711817);
        }
        // Create a mapping from old to new identifiers
        return $this->createIdentifierMap($filesAndFolders, $sourceFolderIdentifier, $relativeTargetPath);
    }

    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param non-empty-string $sourceFolderIdentifier
     * @param non-empty-string $targetFolderIdentifier
     * @param non-empty-string $newFolderName
     * @return true
     */
    public function copyFolderWithinStorage(string $sourceFolderIdentifier, string $targetFolderIdentifier, string $newFolderName): bool
    {
        // This target folder path already includes the topmost level, i.e. the folder this method knows as $folderToCopy.
        // We can thus rely on this folder being present and just create the subfolder we want to copy to.
        $newFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);
        $sourceFolderPath = $this->getAbsolutePath($sourceFolderIdentifier);
        $targetFolderPath = $this->getAbsolutePath($newFolderIdentifier);

        GeneralUtility::mkdir($targetFolderPath);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceFolderPath),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        // Rewind the iterator as this is important for some systems e.g. Windows
        $iterator->rewind();
        while ($iterator->valid()) {
            /** @var \RecursiveDirectoryIterator $current */
            $current = $iterator->current();
            $fileName = $current->getFilename();
            $itemSubPath = GeneralUtility::fixWindowsFilePath((string)$iterator->getSubPathname());
            if ($current->isDir() && !($fileName === '..' || $fileName === '.')) {
                GeneralUtility::mkdir($targetFolderPath . '/' . $itemSubPath);
            } elseif ($current->isFile()) {
                $copySourcePath = $sourceFolderPath . '/' . $itemSubPath;
                $copyTargetPath = $targetFolderPath . '/' . $itemSubPath;
                $result = copy($copySourcePath, $copyTargetPath);
                if ($result === false) {
                    // rollback
                    GeneralUtility::rmdir($targetFolderIdentifier, true);
                    throw new FileOperationErrorException(
                        'Copying resource "' . $copySourcePath . '" to "' . $copyTargetPath . '" failed.',
                        1330119452
                    );
                }
            }
            $iterator->next();
        }
        GeneralUtility::fixPermissions($targetFolderPath, true);
        return true;
    }

    /**
     * Renames a file in this storage.
     *
     * @param non-empty-string $fileIdentifier
     * @param non-empty-string $newName The target path (including the file name!)
     * @return non-empty-string The identifier of the file after renaming
     */
    public function renameFile(string $fileIdentifier, string $newName): string
    {
        // Makes sure the Path given as parameter is valid
        $newName = $this->sanitizeFileName($newName);
        $newIdentifier = rtrim(GeneralUtility::fixWindowsFilePath(PathUtility::dirname($fileIdentifier)), '/') . '/' . $newName;
        $newIdentifier = $this->canonicalizeAndCheckFileIdentifier($newIdentifier);
        // The target should not exist already
        if ($this->fileExists($newIdentifier)) {
            throw new ExistingTargetFileNameException(
                'The target file "' . $newIdentifier . '" already exists.',
                1320291063
            );
        }
        $sourcePath = $this->getAbsolutePath($fileIdentifier);
        $targetPath = $this->getAbsolutePath($newIdentifier);
        $result = rename($sourcePath, $targetPath);
        if ($result === false) {
            throw new \RuntimeException('Renaming file ' . $sourcePath . ' to ' . $targetPath . ' failed.', 1320375115);
        }
        return $newIdentifier;
    }

    /**
     * Renames a folder in this storage.
     *
     * @param non-empty-string $folderIdentifier
     * @param non-empty-string $newName
     * @return array<string, string> A map of old to new file identifiers of all affected files and folders
     * @throws \RuntimeException if renaming the folder failed
     */
    public function renameFolder(string $folderIdentifier, string $newName): array
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
        $newName = $this->sanitizeFileName($newName);

        $newIdentifier = PathUtility::dirname($folderIdentifier) . '/' . $newName;
        $newIdentifier = $this->canonicalizeAndCheckFolderIdentifier($newIdentifier);

        $sourcePath = $this->getAbsolutePath($folderIdentifier);
        $targetPath = $this->getAbsolutePath($newIdentifier);
        // get all files and folders we are going to move, to have a map for updating later.
        $filesAndFolders = $this->retrieveFileAndFoldersInPath($sourcePath, true);
        $result = rename($sourcePath, $targetPath);
        if ($result === false) {
            throw new \RuntimeException(sprintf('Renaming folder "%1$s" to "%2$s" failed."', $sourcePath, $targetPath), 1320375116);
        }
        try {
            // Create a mapping from old to new identifiers
            $identifierMap = $this->createIdentifierMap($filesAndFolders, $folderIdentifier, $newIdentifier);
        } catch (\Exception $e) {
            rename($targetPath, $sourcePath);
            throw new \RuntimeException(
                sprintf(
                    'Creating filename mapping after renaming "%1$s" to "%2$s" failed. Reverted rename operation.\\n\\nOriginal error: %3$s"',
                    $sourcePath,
                    $targetPath,
                    $e->getMessage()
                ),
                1334160746
            );
        }
        return $identifierMap;
    }

    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the ResourceStorage).
     *
     * @param non-empty-string $fileIdentifier
     */
    public function deleteFile(string $fileIdentifier): bool
    {
        $filePath = $this->getAbsolutePath($fileIdentifier);
        $result = unlink($filePath);

        if ($result === false) {
            throw new \RuntimeException('Deletion of file ' . $fileIdentifier . ' failed.', 1320855304);
        }
        return true;
    }

    /**
     * Removes a folder from the hard drive.
     *
     * @param non-empty-string $folderIdentifier
     */
    public function deleteFolder(string $folderIdentifier, bool $deleteRecursively = false): bool
    {
        $folderPath = $this->getAbsolutePath($folderIdentifier);
        $recycleDirectory = $this->getRecycleDirectory($folderPath);
        if (!empty($recycleDirectory) && $folderPath !== $recycleDirectory) {
            $result = $this->recycleFileOrFolder($folderPath, $recycleDirectory);
        } else {
            $result = GeneralUtility::rmdir($folderPath, $deleteRecursively);
        }
        if ($result === false) {
            throw new FileOperationErrorException(
                'Deleting folder "' . $folderIdentifier . '" failed.',
                1330119451
            );
        }
        return $result;
    }

    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
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
     * Creates a new (empty) file and returns the identifier.
     *
     * @param non-empty-string $fileName
     * @param non-empty-string $parentFolderIdentifier
     * @return non-empty-string
     */
    public function createFile(string $fileName, string $parentFolderIdentifier): string
    {
        $fileName = $this->sanitizeFileName(ltrim($fileName, '/'));
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier(
            $parentFolderIdentifier . $fileName
        );
        $absoluteFilePath = $this->getAbsolutePath($fileIdentifier);
        $result = touch($absoluteFilePath);
        GeneralUtility::fixPermissions($absoluteFilePath);
        clearstatcache();
        if ($result !== true) {
            throw new \RuntimeException('Creating file ' . $fileIdentifier . ' failed.', 1320569854);
        }
        return $fileIdentifier;
    }

    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms of
     * processing resources and money) for large files.
     *
     * @param non-empty-string $fileIdentifier
     * @return string The file contents if file exists, otherwise an empty string
     */
    public function getFileContents(string $fileIdentifier): string
    {
        $filePath = $this->getAbsolutePath($fileIdentifier);
        return is_readable($filePath) ? (string)file_get_contents($filePath) : '';
    }

    /**
     * Sets the contents of a file to the specified value.
     *
     * @param non-empty-string $fileIdentifier
     * @return int<0, max> The number of bytes written to the file
     * @throws \RuntimeException if the operation failed
     */
    public function setFileContents(string $fileIdentifier, string $contents): int
    {
        $filePath = $this->getAbsolutePath($fileIdentifier);
        $result = file_put_contents($filePath, $contents);

        // Make sure later calls to filesize() etc. return correct values.
        clearstatcache(true, $filePath);

        if ($result === false) {
            throw new \RuntimeException('Setting contents of file "' . $fileIdentifier . '" failed.', 1325419305);
        }
        return $result;
    }

    /**
     * Returns the role of an item. This is currently only implemented for folder,
     * but could be extended to files as well.
     */
    public function getRole(string $folderIdentifier): string
    {
        $name = PathUtility::basename($folderIdentifier);
        return $this->mappingFolderNameToRole[$name] ?? FolderInterface::ROLE_DEFAULT;
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
        $searchDirectory = PathUtility::dirname($path);
        // Check if file or folder to be deleted is inside a recycler directory
        if ($this->getRole($searchDirectory) === FolderInterface::ROLE_RECYCLER) {
            $searchDirectory = PathUtility::dirname($searchDirectory);
            // Check if file or folder to be deleted is inside the root recycler
            if ($searchDirectory == $rootDirectory) {
                return '';
            }
            $searchDirectory = PathUtility::dirname($searchDirectory);
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
            $searchDirectory = PathUtility::dirname($searchDirectory);
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
