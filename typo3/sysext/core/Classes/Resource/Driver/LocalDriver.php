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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\SelfEmittableLazyOpenStream;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Driver for the local file system
 */
class LocalDriver extends AbstractHierarchicalFilesystemDriver implements StreamableDriverInterface
{
    /**
     * @var string
     */
    const UNSAFE_FILENAME_CHARACTER_EXPRESSION = '\\x00-\\x2C\\/\\x3A-\\x3F\\x5B-\\x60\\x7B-\\xBF';

    /**
     * The absolute base path. It always contains a trailing slash.
     *
     * @var string
     */
    protected $absoluteBasePath;

    /**
     * A list of all supported hash algorithms, written all lower case.
     *
     * @var array
     */
    protected $supportedHashAlgorithms = ['sha1', 'md5'];

    /**
     * The base URL that points to this driver's storage. As long is this
     * is not set, it is assumed that this folder is not publicly available
     *
     * @var string
     */
    protected $baseUri;

    /** @var array */
    protected $mappingFolderNameToRole = [
        '_recycler_' => FolderInterface::ROLE_RECYCLER,
        '_temp_' => FolderInterface::ROLE_TEMPORARY,
        'user_upload' => FolderInterface::ROLE_USERUPLOAD,
    ];

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration = [])
    {
        parent::__construct($configuration);
        // The capabilities default of this driver. See CAPABILITY_* constants for possible values
        $this->capabilities =
            ResourceStorage::CAPABILITY_BROWSABLE
            | ResourceStorage::CAPABILITY_PUBLIC
            | ResourceStorage::CAPABILITY_WRITABLE
            | ResourceStorage::CAPABILITY_HIERARCHICAL_IDENTIFIERS;
    }

    /**
     * Merges the capabilites merged by the user at the storage
     * configuration into the actual capabilities of the driver
     * and returns the result.
     *
     * @param int $capabilities
     * @return int
     */
    public function mergeConfigurationCapabilities($capabilities)
    {
        $this->capabilities &= $capabilities;

        return $this->capabilities;
    }

    /**
     * Processes the configuration for this driver.
     */
    public function processConfiguration()
    {
        $this->absoluteBasePath = $this->calculateBasePath($this->configuration);
        $this->determineBaseUrl();
        if ($this->baseUri === null) {
            // remove public flag
            $this->capabilities &= ~ResourceStorage::CAPABILITY_PUBLIC;
        }
    }

    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     */
    public function initialize()
    {
    }

    /**
     * Determines the base URL for this driver, from the configuration or
     * the TypoScript frontend object
     */
    protected function determineBaseUrl()
    {
        // only calculate baseURI if the storage does not enforce jumpUrl Script
        if ($this->hasCapability(ResourceStorage::CAPABILITY_PUBLIC)) {
            if (GeneralUtility::isFirstPartOfStr($this->absoluteBasePath, Environment::getPublicPath())) {
                // use site-relative URLs
                $temporaryBaseUri = rtrim(PathUtility::stripPathSitePrefix($this->absoluteBasePath), '/');
                if ($temporaryBaseUri !== '') {
                    $uriParts = explode('/', $temporaryBaseUri);
                    $uriParts = array_map('rawurlencode', $uriParts);
                    $temporaryBaseUri = implode('/', $uriParts) . '/';
                }
                $this->baseUri = $temporaryBaseUri;
            } elseif (isset($this->configuration['baseUri']) && GeneralUtility::isValidUrl($this->configuration['baseUri'])) {
                $this->baseUri = rtrim($this->configuration['baseUri'], '/') . '/';
            }
        }
    }

    /**
     * Calculates the absolute path to this drivers storage location.
     *
     * @throws Exception\InvalidConfigurationException
     * @param array $configuration
     * @return string
     * @throws Exception\InvalidPathException
     */
    protected function calculateBasePath(array $configuration)
    {
        if (!array_key_exists('basePath', $configuration) || empty($configuration['basePath'])) {
            throw new Exception\InvalidConfigurationException(
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
        if (!is_dir($absoluteBasePath)) {
            throw new Exception\InvalidConfigurationException(
                'Base path "' . $absoluteBasePath . '" does not exist or is no directory.',
                1299233097
            );
        }
        return $absoluteBasePath;
    }

    /**
     * Returns the public URL to a file.
     * For the local driver, this will always return a path relative to public web path.
     *
     * @param string $identifier
     * @return string|null NULL if file is missing or deleted, the generated url otherwise
     * @throws \TYPO3\CMS\Core\Resource\Exception
     */
    public function getPublicUrl($identifier)
    {
        $publicUrl = null;
        if ($this->baseUri !== null) {
            $uriParts = explode('/', ltrim($identifier, '/'));
            $uriParts = array_map('rawurlencode', $uriParts);
            $identifier = implode('/', $uriParts);
            $publicUrl = $this->baseUri . $identifier;
        }
        return $publicUrl;
    }

    /**
     * Returns the Identifier of the root level folder of the storage.
     *
     * @return string
     */
    public function getRootLevelFolder()
    {
        return '/';
    }

    /**
     * Returns identifier of the default folder new files should be put into.
     *
     * @return string
     */
    public function getDefaultFolder()
    {
        $identifier = '/user_upload/';
        $createFolder = !$this->folderExists($identifier);
        if ($createFolder === true) {
            $identifier = $this->createFolder('user_upload');
        }
        return $identifier;
    }

    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a rootlevel folder will be created
     *
     * @param string $newFolderName
     * @param string $parentFolderIdentifier
     * @param bool $recursive
     * @return string the Identifier of the new folder
     */
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false)
    {
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
        $newFolderName = trim($newFolderName, '/');
        if ($recursive === false) {
            $newFolderName = $this->sanitizeFileName($newFolderName);
            $newIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier . $newFolderName . '/');
            GeneralUtility::mkdir($this->getAbsolutePath($newIdentifier));
        } else {
            $parts = GeneralUtility::trimExplode('/', $newFolderName);
            $parts = array_map([$this, 'sanitizeFileName'], $parts);
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
     * @param string $fileIdentifier In the case of the LocalDriver, this is the (relative) path to the file.
     * @param array $propertiesToExtract Array of properties which should be extracted, if empty all will be extracted
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = [])
    {
        $absoluteFilePath = $this->getAbsolutePath($fileIdentifier);
        // don't use $this->fileExists() because we need the absolute path to the file anyways, so we can directly
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
     * @return array
     * @throws Exception\FolderDoesNotExistException
     */
    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);

        if (!$this->folderExists($folderIdentifier)) {
            throw new Exception\FolderDoesNotExistException(
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
            'storage' => $this->storageUid
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
     * @param string $charset Charset of the a fileName (defaults to utf-8)
     * @return string Output string with any characters not matching [.a-zA-Z0-9_-] is substituted by '_' and trailing dots removed
     * @throws Exception\InvalidFileNameException
     */
    public function sanitizeFileName($fileName, $charset = 'utf-8')
    {
        // Handle UTF-8 characters
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
            // Allow ".", "-", 0-9, a-z, A-Z and everything beyond U+C0 (latin capital letter a with grave)
            $cleanFileName = preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . ']/u', '_', trim($fileName));
        } else {
            $fileName = GeneralUtility::makeInstance(CharsetConverter::class)->specCharsToASCII($charset, $fileName);
            // Replace unwanted characters with underscores
            $cleanFileName = preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . '\\xC0-\\xFF]/', '_', trim($fileName));
        }
        // Strip trailing dots and return
        $cleanFileName = rtrim($cleanFileName, '.');
        if ($cleanFileName === '') {
            throw new Exception\InvalidFileNameException(
                'File name ' . $fileName . ' is invalid.',
                1320288991
            );
        }
        return $cleanFileName;
    }

    /**
     * Generic wrapper for extracting a list of items from a path.
     *
     * @param string $folderIdentifier
     * @param int $start The position to start the listing; if not set, start from the beginning
     * @param int $numberOfItems The number of items to list; if set to zero, all items are returned
     * @param array $filterMethods The filter methods used to filter the directory items
     * @param bool $includeFiles
     * @param bool $includeDirs
     * @param bool $recursive
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getDirectoryItemList($folderIdentifier, $start = 0, $numberOfItems = 0, array $filterMethods, $includeFiles = true, $includeDirs = true, $recursive = false, $sort = '', $sortRev = false)
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
        $c = $numberOfItems > 0 ? $numberOfItems : - 1;
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
                    $items[$iteratorItem['identifier']] = $iteratorItem['identifier'];
                    // Decrement item counter to make sure we only return $numberOfItems
                    // we cannot do this earlier in the method (unlike moving the iterator forward) because we only add the
                    // item here
                    --$c;
                }
            } catch (Exception\InvalidPathException $e) {
            }
        }
        return $items;
    }

    /**
     * Applies a set of filter methods to a file name to find out if it should be used or not. This is e.g. used by
     * directory listings.
     *
     * @param array $filterMethods The filter methods to use
     * @param string $itemName
     * @param string $itemIdentifier
     * @param string $parentIdentifier
     * @throws \RuntimeException
     * @return bool
     */
    protected function applyFilterMethodsToDirectoryItem(array $filterMethods, $itemName, $itemIdentifier, $parentIdentifier)
    {
        foreach ($filterMethods as $filter) {
            if (is_callable($filter)) {
                $result = call_user_func($filter, $itemName, $itemIdentifier, $parentIdentifier, [], $this);
                // We have to use -1 as the „don't include“ return value, as call_user_func() will return FALSE
                // If calling the method succeeded and thus we can't use that as a return value.
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
     * Returns a file inside the specified path
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return string File Identifier
     */
    public function getFileInFolder($fileName, $folderIdentifier)
    {
        return $this->canonicalizeAndCheckFileIdentifier($folderIdentifier . '/' . $fileName);
    }

    /**
     * Returns a list of files inside the specified path
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
     * @return array of FileIdentifiers
     */
    public function getFilesInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $filenameFilterCallbacks = [], $sort = '', $sortRev = false)
    {
        return $this->getDirectoryItemList($folderIdentifier, $start, $numberOfItems, $filenameFilterCallbacks, true, false, $recursive, $sort, $sortRev);
    }

    /**
     * Returns the number of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @return int Number of files in folder
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = [])
    {
        return count($this->getFilesInFolder($folderIdentifier, 0, 0, $recursive, $filenameFilterCallbacks));
    }

    /**
     * Returns a list of folders inside the specified path
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
     * @return array of Folder Identifier
     */
    public function getFoldersInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $folderNameFilterCallbacks = [], $sort = '', $sortRev = false)
    {
        return $this->getDirectoryItemList($folderIdentifier, $start, $numberOfItems, $folderNameFilterCallbacks, false, true, $recursive, $sort, $sortRev);
    }

    /**
     * Returns the number of folders inside the specified path
     *
     * @param string  $folderIdentifier
     * @param bool $recursive
     * @param array   $folderNameFilterCallbacks callbacks for filtering the items
     * @return int Number of folders in folder
     */
    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = [])
    {
        return count($this->getFoldersInFolder($folderIdentifier, 0, 0, $recursive, $folderNameFilterCallbacks));
    }

    /**
     * Returns a list with the names of all files and folders in a path, optionally recursive.
     *
     * @param string $path The absolute path
     * @param bool $recursive If TRUE, recursively fetches files and folders
     * @param bool $includeFiles
     * @param bool $includeDirs
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array
     */
    protected function retrieveFileAndFoldersInPath($path, $recursive = false, $includeFiles = true, $includeDirs = true, $sort = '', $sortRev = false)
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
            $isDirectory = $isFile ? false : $entry->isDir();
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
                'type' => $isDirectory ? 'dir' : 'file'
            ];
            $directoryEntries[$entryIdentifier] = $entryArray;
            $iterator->next();
        }
        return $this->sortDirectoryEntries($directoryEntries, $sort, $sortRev);
    }

    /**
     * Sort the directory entries by a certain key
     *
     * @param array $directoryEntries Array of directory entry arrays from
     *                                retrieveFileAndFoldersInPath()
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array Sorted entries. Content of the keys is undefined.
     */
    protected function sortDirectoryEntries($directoryEntries, $sort = '', $sortRev = false)
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
     * @return array
     */
    protected function extractFileInformation($filePath, $containerPath, array $propertiesToExtract = [])
    {
        if (empty($propertiesToExtract)) {
            $propertiesToExtract = [
                'size', 'atime', 'mtime', 'ctime', 'mimetype', 'name', 'extension',
                'identifier', 'identifier_hash', 'storage', 'folder_hash'
            ];
        }
        $fileInformation = [];
        foreach ($propertiesToExtract as $property) {
            $fileInformation[$property] = $this->getSpecificFileInformation($filePath, $containerPath, $property);
        }
        return $fileInformation;
    }

    /**
     * Extracts a specific FileInformation from the FileSystems.
     *
     * @param string $fileIdentifier
     * @param string $containerPath
     * @param string $property
     *
     * @return bool|int|string
     * @throws \InvalidArgumentException
     */
    public function getSpecificFileInformation($fileIdentifier, $containerPath, $property)
    {
        $identifier = $this->canonicalizeAndCheckFileIdentifier($containerPath . PathUtility::basename($fileIdentifier));

        /** @var FileInfo $fileInfo */
        $fileInfo = GeneralUtility::makeInstance(FileInfo::class, $fileIdentifier);
        switch ($property) {
            case 'size':
                return $fileInfo->getSize();
            case 'atime':
                return $fileInfo->getATime();
            case 'mtime':
                return $fileInfo->getMTime();
            case 'ctime':
                return $fileInfo->getCTime();
            case 'name':
                return PathUtility::basename($fileIdentifier);
            case 'extension':
                return PathUtility::pathinfo($fileIdentifier, PATHINFO_EXTENSION);
            case 'mimetype':
                return (string)$fileInfo->getMimeType();
            case 'identifier':
                return $identifier;
            case 'storage':
                return $this->storageUid;
            case 'identifier_hash':
                return $this->hashIdentifier($identifier);
            case 'folder_hash':
                return $this->hashIdentifier($this->getParentFolderIdentifierOfIdentifier($identifier));
            default:
                throw new \InvalidArgumentException(sprintf('The information "%s" is not available.', $property), 1476047422);
        }
    }

    /**
     * Returns the absolute path of the folder this driver operates on.
     *
     * @return string
     */
    protected function getAbsoluteBasePath()
    {
        return $this->absoluteBasePath;
    }

    /**
     * Returns the absolute path of a file or folder.
     *
     * @param string $fileIdentifier
     * @return string
     * @throws Exception\InvalidPathException
     */
    protected function getAbsolutePath($fileIdentifier)
    {
        $relativeFilePath = ltrim($this->canonicalizeAndCheckFileIdentifier($fileIdentifier), '/');
        $path = $this->absoluteBasePath . $relativeFilePath;
        return $path;
    }

    /**
     * Creates a (cryptographic) hash for a file.
     *
     * @param string $fileIdentifier
     * @param string $hashAlgorithm The hash algorithm to use
     * @return string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function hash($fileIdentifier, $hashAlgorithm)
    {
        if (!in_array($hashAlgorithm, $this->supportedHashAlgorithms)) {
            throw new \InvalidArgumentException('Hash algorithm "' . $hashAlgorithm . '" is not supported.', 1304964032);
        }
        switch ($hashAlgorithm) {
            case 'sha1':
                $hash = sha1_file($this->getAbsolutePath($fileIdentifier));
                break;
            case 'md5':
                $hash = md5_file($this->getAbsolutePath($fileIdentifier));
                break;
            default:
                throw new \RuntimeException('Hash algorithm ' . $hashAlgorithm . ' is not implemented.', 1329644451);
        }
        return $hash;
    }

    /**
     * Adds a file from the local server hard disk to a given path in TYPO3s virtual file system.
     * This assumes that the local file exists, so no further check is done here!
     * After a successful the original file must not exist anymore.
     *
     * @param string $localFilePath within public web path
     * @param string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param bool $removeOriginal if set the original file will be removed after successful operation
     * @return string the identifier of the new file
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true)
    {
        $localFilePath = $this->canonicalizeAndCheckFilePath($localFilePath);
        // as for the "virtual storage" for backwards-compatibility, this check always fails, as the file probably lies under public web path
        // thus, it is not checked here
        // @todo is check in storage
        if (GeneralUtility::isFirstPartOfStr($localFilePath, $this->absoluteBasePath) && $this->storageUid > 0) {
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
     * Checks if a file exists.
     *
     * @param string $fileIdentifier
     *
     * @return bool
     */
    public function fileExists($fileIdentifier)
    {
        $absoluteFilePath = $this->getAbsolutePath($fileIdentifier);
        return is_file($absoluteFilePath);
    }

    /**
     * Checks if a file inside a folder exists
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return bool
     */
    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        $identifier = $folderIdentifier . '/' . $fileName;
        $identifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        return $this->fileExists($identifier);
    }

    /**
     * Checks if a folder exists.
     *
     * @param string $folderIdentifier
     *
     * @return bool
     */
    public function folderExists($folderIdentifier)
    {
        $absoluteFilePath = $this->getAbsolutePath($folderIdentifier);
        return is_dir($absoluteFilePath);
    }

    /**
     * Checks if a folder inside a folder exists.
     *
     * @param string $folderName
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        $identifier = $folderIdentifier . '/' . $folderName;
        $identifier = $this->canonicalizeAndCheckFolderIdentifier($identifier);
        return $this->folderExists($identifier);
    }

    /**
     * Returns the Identifier for a folder within a given folder.
     *
     * @param string $folderName The name of the target folder
     * @param string $folderIdentifier
     * @return string
     */
    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier . '/' . $folderName);
        return $folderIdentifier;
    }

    /**
     * Replaces the contents (and file-specific metadata) of a file object with a local file.
     *
     * @param string $fileIdentifier
     * @param string $localFilePath
     * @return bool TRUE if the operation succeeded
     * @throws \RuntimeException
     */
    public function replaceFile($fileIdentifier, $localFilePath)
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
        return $result;
    }

    /**
     * Copies a file *within* the current storage.
     * Note that this is only about an intra-storage copy action, where a file is just
     * copied to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $fileName
     * @return string the Identifier of the new file
     */
    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName)
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
     * Note that this is only about an inner-storage move action, where a file is just
     * moved to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     * @return string
     * @throws \RuntimeException
     */
    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName)
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
     *
     * @param string $fileIdentifier
     * @return string The temporary path
     * @throws \RuntimeException
     */
    protected function copyFileToTemporaryPath($fileIdentifier)
    {
        $sourcePath = $this->getAbsolutePath($fileIdentifier);
        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
        $result = copy($sourcePath, $temporaryPath);
        touch($temporaryPath, filemtime($sourcePath));
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
     *
     * @param string $filePath
     * @param string $recycleDirectory
     * @return bool
     */
    protected function recycleFileOrFolder($filePath, $recycleDirectory)
    {
        $destinationFile = $recycleDirectory . '/' . PathUtility::basename($filePath);
        if (file_exists($destinationFile)) {
            $timeStamp = \DateTimeImmutable::createFromFormat('U.u', microtime(true))->format('YmdHisu');
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
     *
     * @param array $filesAndFolders
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     *
     * @return array
     * @throws Exception\FileOperationErrorException
     */
    protected function createIdentifierMap(array $filesAndFolders, $sourceFolderIdentifier, $targetFolderIdentifier)
    {
        $identifierMap = [];
        $identifierMap[$sourceFolderIdentifier] = $targetFolderIdentifier;
        foreach ($filesAndFolders as $oldItem) {
            if ($oldItem['type'] === 'dir') {
                $oldIdentifier = $oldItem['identifier'];
                $newIdentifier = $this->canonicalizeAndCheckFolderIdentifier(
                    str_replace($sourceFolderIdentifier, $targetFolderIdentifier, $oldItem['identifier'])
                );
            } else {
                $oldIdentifier = $oldItem['identifier'];
                $newIdentifier = $this->canonicalizeAndCheckFileIdentifier(
                    str_replace($sourceFolderIdentifier, $targetFolderIdentifier, $oldItem['identifier'])
                );
            }
            if (!file_exists($this->getAbsolutePath($newIdentifier))) {
                throw new Exception\FileOperationErrorException(
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
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     *
     * @return array A map of old to new file identifiers
     * @throws \RuntimeException
     */
    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
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
        $identifierMap = $this->createIdentifierMap($filesAndFolders, $sourceFolderIdentifier, $relativeTargetPath);
        return $identifierMap;
    }

    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     *
     * @return bool
     * @throws Exception\FileOperationErrorException
     */
    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        // This target folder path already includes the topmost level, i.e. the folder this method knows as $folderToCopy.
        // We can thus rely on this folder being present and just create the subfolder we want to copy to.
        $newFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);
        $sourceFolderPath = $this->getAbsolutePath($sourceFolderIdentifier);
        $targetFolderPath = $this->getAbsolutePath($newFolderIdentifier);

        mkdir($targetFolderPath);
        /** @var \RecursiveDirectoryIterator $iterator */
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
            $itemSubPath = GeneralUtility::fixWindowsFilePath($iterator->getSubPathname());
            if ($current->isDir() && !($fileName === '..' || $fileName === '.')) {
                GeneralUtility::mkdir($targetFolderPath . '/' . $itemSubPath);
            } elseif ($current->isFile()) {
                $copySourcePath = $sourceFolderPath . '/' . $itemSubPath;
                $copyTargetPath = $targetFolderPath . '/' . $itemSubPath;
                $result = copy($copySourcePath, $copyTargetPath);
                if ($result === false) {
                    // rollback
                    GeneralUtility::rmdir($targetFolderIdentifier, true);
                    throw new Exception\FileOperationErrorException(
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
     * @param string $fileIdentifier
     * @param string $newName The target path (including the file name!)
     * @return string The identifier of the file after renaming
     * @throws Exception\ExistingTargetFileNameException
     * @throws \RuntimeException
     */
    public function renameFile($fileIdentifier, $newName)
    {
        // Makes sure the Path given as parameter is valid
        $newName = $this->sanitizeFileName($newName);
        $newIdentifier = rtrim(GeneralUtility::fixWindowsFilePath(PathUtility::dirname($fileIdentifier)), '/') . '/' . $newName;
        $newIdentifier = $this->canonicalizeAndCheckFileIdentifier($newIdentifier);
        // The target should not exist already
        if ($this->fileExists($newIdentifier)) {
            throw new Exception\ExistingTargetFileNameException(
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
     * @param string $folderIdentifier
     * @param string $newName
     * @return array A map of old to new file identifiers of all affected files and folders
     * @throws \RuntimeException if renaming the folder failed
     */
    public function renameFolder($folderIdentifier, $newName)
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
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @param string $fileIdentifier
     * @return bool TRUE if deleting the file succeeded
     * @throws \RuntimeException
     */
    public function deleteFile($fileIdentifier)
    {
        $filePath = $this->getAbsolutePath($fileIdentifier);
        $result = unlink($filePath);

        if ($result === false) {
            throw new \RuntimeException('Deletion of file ' . $fileIdentifier . ' failed.', 1320855304);
        }
        return $result;
    }

    /**
     * Removes a folder from this storage.
     *
     * @param string $folderIdentifier
     * @param bool $deleteRecursively
     * @return bool
     * @throws Exception\FileOperationErrorException
     * @throws Exception\InvalidPathException
     */
    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        $folderPath = $this->getAbsolutePath($folderIdentifier);
        $recycleDirectory = $this->getRecycleDirectory($folderPath);
        if (!empty($recycleDirectory) && $folderPath !== $recycleDirectory) {
            $result = $this->recycleFileOrFolder($folderPath, $recycleDirectory);
        } else {
            $result = GeneralUtility::rmdir($folderPath, $deleteRecursively);
        }
        if ($result === false) {
            throw new Exception\FileOperationErrorException(
                'Deleting folder "' . $folderIdentifier . '" failed.',
                1330119451
            );
        }
        return $result;
    }

    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param string $folderIdentifier
     * @return bool TRUE if there are no files and folders within $folder
     */
    public function isFolderEmpty($folderIdentifier)
    {
        $path = $this->getAbsolutePath($folderIdentifier);
        $dirHandle = opendir($path);
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
     * Returns (a local copy of) a file for processing it. This makes a copy
     * first when in writable mode, so if you change the file, you have to update it yourself afterwards.
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read operations.
     *                          This might speed up things, e.g. by using a cached local version.
     *                          Never modify the file if you have set this flag!
     * @return string The path to the file on the local disk
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        if ($writable === false) {
            return $this->getAbsolutePath($fileIdentifier);
        }
        return $this->copyFileToTemporaryPath($fileIdentifier);
    }

    /**
     * Returns the permissions of a file/folder as an array (keys r, w) of boolean flags
     *
     * @param string $identifier
     * @return array
     * @throws Exception\ResourcePermissionsUnavailableException
     */
    public function getPermissions($identifier)
    {
        $path = $this->getAbsolutePath($identifier);
        $permissionBits = fileperms($path);
        if ($permissionBits === false) {
            throw new Exception\ResourcePermissionsUnavailableException('Error while fetching permissions for ' . $path, 1319455097);
        }
        return [
            'r' => (bool)is_readable($path),
            'w' => (bool)is_writable($path)
        ];
    }

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder. It will also return
     * TRUE if both canonicalized identifiers are equal.
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     * @return bool TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin($folderIdentifier, $identifier)
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
        return GeneralUtility::isFirstPartOfStr($entryIdentifier, $folderIdentifier);
    }

    /**
     * Creates a new (empty) file and returns the identifier.
     *
     * @param string $fileName
     * @param string $parentFolderIdentifier
     * @return string
     * @throws \RuntimeException
     */
    public function createFile($fileName, $parentFolderIdentifier)
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
     * @param string $fileIdentifier
     * @return string The file contents
     */
    public function getFileContents($fileIdentifier)
    {
        $filePath = $this->getAbsolutePath($fileIdentifier);
        return file_get_contents($filePath);
    }

    /**
     * Sets the contents of a file to the specified value.
     *
     * @param string $fileIdentifier
     * @param string $contents
     * @return int The number of bytes written to the file
     * @throws \RuntimeException if the operation failed
     */
    public function setFileContents($fileIdentifier, $contents)
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
     * Returns the role of an item (currently only folders; can later be extended for files as well)
     *
     * @param string $folderIdentifier
     * @return string
     */
    public function getRole($folderIdentifier)
    {
        $name = PathUtility::basename($folderIdentifier);
        $role = $this->mappingFolderNameToRole[$name] ?? FolderInterface::ROLE_DEFAULT;
        return $role;
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     */
    public function dumpFileContents($identifier)
    {
        readfile($this->getAbsolutePath($this->canonicalizeAndCheckFileIdentifier($identifier)), 0);
    }

    /**
     * Stream file using a PSR-7 Response object.
     *
     * @param string $identifier
     * @param array $properties
     * @return ResponseInterface
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
     * Get the path of the nearest recycler folder of a given $path.
     * Return an empty string if there is no recycler folder available.
     *
     * @param string $path
     * @return string
     */
    protected function getRecycleDirectory($path)
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
}
