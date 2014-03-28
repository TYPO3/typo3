<?php
namespace TYPO3\CMS\Core\Resource\Driver;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
 * (c) 2013 Stefan Neufeind <info (at) speedpartner.de>
 * (c) 2013 Steffen Ritter <steffen.ritter@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Driver for the local file system
 *
 */
class LocalDriver extends AbstractHierarchicalFilesystemDriver {

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
	protected $supportedHashAlgorithms = array('sha1', 'md5');

	/**
	 * The base URL that points to this driver's storage. As long is this
	 * is not set, it is assumed that this folder is not publicly available
	 *
	 * @var string
	 */
	protected $baseUri = NULL;

	/**
	 * @var \TYPO3\CMS\Core\Charset\CharsetConverter
	 */
	protected $charsetConversion;

	/** @var array */
	protected $mappingFolderNameToRole = array(
		'_recycler_' => FolderInterface::ROLE_RECYCLER,
		'_temp_' => FolderInterface::ROLE_TEMPORARY,
		'user_upload' => FolderInterface::ROLE_USERUPLOAD,
	);

	/**
	 * @param array $configuration
	 */
	public function __construct(array $configuration = array()) {
		parent::__construct($configuration);
		// The capabilities default of this driver. See CAPABILITY_* constants for possible values
		$this->capabilities =
			\TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_BROWSABLE
			| \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_PUBLIC
			| \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_WRITABLE;
	}

	/**
	 * Merges the capabilites merged by the user at the storage
	 * configuration into the actual capabilities of the driver
	 * and returns the result.
	 *
	 * @param integer $capabilities
	 *
	 * @return integer
	 */
	public function mergeConfigurationCapabilities($capabilities) {
		$this->capabilities &= $capabilities;
		return $this->capabilities;
	}


	/**
	 * Processes the configuration for this driver.
	 *
	 * @return void
	 */
	public function processConfiguration() {
		$this->absoluteBasePath = $this->calculateBasePath($this->configuration);
		$this->determineBaseUrl();
		if ($this->baseUri === NULL) {
			// remove public flag
			$this->capabilities &= ~\TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_PUBLIC;
		}
	}

	/**
	 * Initializes this object. This is called by the storage after the driver
	 * has been attached.
	 *
	 * @return void
	 */
	public function initialize() {
	}

	/**
	 * Determines the base URL for this driver, from the configuration or
	 * the TypoScript frontend object
	 *
	 * @return void
	 */
	protected function determineBaseUrl() {
		// only calculate baseURI if the storage does not enforce jumpUrl Script
		if ($this->hasCapability(\TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_PUBLIC)) {
			if (GeneralUtility::isFirstPartOfStr($this->absoluteBasePath, PATH_site)) {
				// use site-relative URLs
				$temporaryBaseUri = rtrim(\TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($this->absoluteBasePath), '/');
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
	 * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException
	 * @param array $configuration
	 * @return string
	 */
	protected function calculateBasePath(array $configuration) {
		if (!array_key_exists('basePath', $configuration) || empty($configuration['basePath'])) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException(
				'Configuration must contain base path.',
				1346510477
			);
		}

		if ($configuration['pathType'] === 'relative') {
			$relativeBasePath = $configuration['basePath'];
			$absoluteBasePath = PATH_site . $relativeBasePath;
		} else {
			$absoluteBasePath = $configuration['basePath'];
		}
		$this->canonicalizeAndCheckFilePath($absoluteBasePath);
		$absoluteBasePath = rtrim($absoluteBasePath, '/') . '/';
		if (!is_dir($absoluteBasePath)) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException(
				'Base path "' . $absoluteBasePath . '" does not exist or is no directory.',
				1299233097
			);
		}
		return $absoluteBasePath;
	}

	/**
	 * Returns the public URL to a file.
	 * For the local driver, this will always return a path relative to PATH_site.
	 *
	 * @param string $identifier
	 * @return string
	 * @throws \TYPO3\CMS\Core\Resource\Exception
	 */
	public function getPublicUrl($identifier) {
		$publicUrl = NULL;
		if ($this->baseUri !== NULL) {
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
	public function getRootLevelFolder() {
		return '/';
	}

	/**
	 * Returns identifier of the default folder new files should be put into.
	 *
	 * @return string
	 */
	public function getDefaultFolder() {
		$identifier = '/user_upload/';
		$createFolder = !$this->folderExists($identifier);
		if ($createFolder === TRUE) {
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
	 * @param boolean $recursive
	 * @return string the Identifier of the new folder
	 */
	public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = FALSE) {
		$parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
		$newFolderName = trim($newFolderName, '/');
		if ($recursive == FALSE) {
			$newFolderName = $this->sanitizeFileName($newFolderName);
			$newIdentifier = $parentFolderIdentifier . $newFolderName . '/';
			GeneralUtility::mkdir($this->getAbsoluteBasePath() . $newIdentifier);
		} else {
			$parts = GeneralUtility::trimExplode('/', $newFolderName);
			$parts = array_map(array($this, 'sanitizeFileName'), $parts);
			$newFolderName = implode('/', $parts);
			$newIdentifier = $parentFolderIdentifier . $newFolderName . '/';
			GeneralUtility::mkdir_deep($this->getAbsoluteBasePath() . $parentFolderIdentifier, $newFolderName);
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
	public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = array()) {
		$dirPath = PathUtility::dirname($fileIdentifier);
		$dirPath = $this->canonicalizeAndCheckFolderIdentifier($dirPath);

		$absoluteFilePath = $this->getAbsolutePath($fileIdentifier);
		// don't use $this->fileExists() because we need the absolute path to the file anyways, so we can directly
		// use PHP's filesystem method.
		if (!file_exists($absoluteFilePath)) {
			throw new \InvalidArgumentException('File ' . $fileIdentifier . ' does not exist.', 1314516809);
		}
		return $this->extractFileInformation($absoluteFilePath, $dirPath, $propertiesToExtract);
	}

	/**
	 * Returns information about a folder.
	 *
	 * @param string $folderIdentifier In the case of the LocalDriver, this is the (relative) path to the file.
	 * @return array
	 * @throws \TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException
	 */
	public function getFolderInfoByIdentifier($folderIdentifier) {
		$folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);

		if (!$this->folderExists($folderIdentifier)) {
			throw new \TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException(
				'File ' . $folderIdentifier . ' does not exist.',
				1314516810
			);
		}
		return array(
			'identifier' => $folderIdentifier,
			'name' => PathUtility::basename($folderIdentifier),
			'storage' => $this->storageUid
		);
	}

	/**
	 * Returns a string where any character not matching [.a-zA-Z0-9_-] is
	 * substituted by '_'
	 * Trailing dots are removed
	 *
	 * Previously in \TYPO3\CMS\Core\Utility\File\BasicFileUtility::cleanFileName()
	 *
	 * @param string $fileName Input string, typically the body of a fileName
	 * @param string $charset Charset of the a fileName (defaults to current charset; depending on context)
	 * @return string Output string with any characters not matching [.a-zA-Z0-9_-] is substituted by '_' and trailing dots removed
	 * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException
	 */
	public function sanitizeFileName($fileName, $charset = '') {
		// Handle UTF-8 characters
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
			// Allow ".", "-", 0-9, a-z, A-Z and everything beyond U+C0 (latin capital letter a with grave)
			$cleanFileName = preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . ']/u', '_', trim($fileName));
		} else {
			// Define character set
			if (!$charset) {
				if (TYPO3_MODE === 'FE') {
					$charset = $GLOBALS['TSFE']->renderCharset;
				} else {
					// default for Backend
					$charset = 'utf-8';
				}
			}
			// If a charset was found, convert fileName
			if ($charset) {
				$fileName = $this->getCharsetConversion()->specCharsToASCII($charset, $fileName);
			}
			// Replace unwanted characters by underscores
			$cleanFileName = preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . '\\xC0-\\xFF]/', '_', trim($fileName));
		}
		// Strip trailing dots and return
		$cleanFileName = preg_replace('/\\.*$/', '', $cleanFileName);
		if (!$cleanFileName) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException(
				'File name ' . $cleanFileName . ' is invalid.',
				1320288991
			);
		}
		return $cleanFileName;
	}

	/**
	 * Generic wrapper for extracting a list of items from a path.
	 *
	 * @param string $folderIdentifier
	 * @param integer $start The position to start the listing; if not set, start from the beginning
	 * @param integer $numberOfItems The number of items to list; if set to zero, all items are returned
	 * @param array $filterMethods The filter methods used to filter the directory items
	 * @param boolean $includeFiles
	 * @param boolean $includeDirs
	 * @param boolean $recursive
	 *
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function getDirectoryItemList($folderIdentifier, $start = 0, $numberOfItems = 0, array $filterMethods, $includeFiles = TRUE, $includeDirs = TRUE, $recursive = FALSE) {
		$folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
		$realPath = $this->getAbsolutePath($folderIdentifier);
		if (!is_dir($realPath)) {
			throw new \InvalidArgumentException(
				'Cannot list items in directory ' . $folderIdentifier . ' - does not exist or is no directory',
				1314349666
			);
		}

		if ($start > 0) {
			$start--;
		}

		// Fetch the files and folders and sort them by name; we have to do
		// this here because the directory iterator does return them in
		// an arbitrary order
		$items = $this->retrieveFileAndFoldersInPath($realPath, $recursive, $includeFiles, $includeDirs);
		uksort(
			$items,
			array('\\TYPO3\\CMS\\Core\\Utility\\ResourceUtility', 'recursiveFileListSortingHelper')
		);

		$iterator = new \ArrayIterator($items);
		if ($iterator->count() === 0) {
			return array();
		}
		$iterator->seek($start);

		// $c is the counter for how many items we still have to fetch (-1 is unlimited)
		$c = $numberOfItems > 0 ? $numberOfItems : - 1;
		$items = array();
		while ($iterator->valid() && ($numberOfItems === 0 || $c > 0)) {
			// $iteratorItem is the file or folder name
			$iteratorItem = $iterator->current();
			// go on to the next iterator item now as we might skip this one early
			$iterator->next();

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


			$items[$iteratorItem['identifier']] = $iteratorItem['identifier'];
			// Decrement item counter to make sure we only return $numberOfItems
			// we cannot do this earlier in the method (unlike moving the iterator forward) because we only add the
			// item here
			--$c;
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
	 * @return boolean
	 */
	protected function applyFilterMethodsToDirectoryItem(array $filterMethods, $itemName, $itemIdentifier, $parentIdentifier) {
		foreach ($filterMethods as $filter) {
			if (is_array($filter)) {
				$result = call_user_func($filter, $itemName, $itemIdentifier, $parentIdentifier, array(), $this);
				// We have to use -1 as the „don't include“ return value, as call_user_func() will return FALSE
				// If calling the method succeeded and thus we can't use that as a return value.
				if ($result === -1) {
					return FALSE;
				} elseif ($result === FALSE) {
					throw new \RuntimeException('Could not apply file/folder name filter ' . $filter[0] . '::' . $filter[1]);
				}
			}
		}
		return TRUE;
	}

	/**
	 * Returns a list of files inside the specified path
	 *
	 * @param string $folderIdentifier
	 * @param integer $start
	 * @param integer $numberOfItems
	 * @param boolean $recursive
	 * @param array $filenameFilterCallbacks The method callbacks to use for filtering the items
	 *
	 * @return array of FileIdentifiers
	 */
	public function getFilesInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = FALSE, array $filenameFilterCallbacks = array()) {
		return $this->getDirectoryItemList($folderIdentifier, $start, $numberOfItems, $filenameFilterCallbacks, TRUE, FALSE, $recursive);
	}

	/**
	 * Returns a list of folders inside the specified path
	 *
	 * @param string $folderIdentifier
	 * @param integer $start
	 * @param integer $numberOfItems
	 * @param boolean $recursive
	 * @param array $folderNameFilterCallbacks The method callbacks to use for filtering the items
	 *
	 * @return array of Folder Identifier
	 */
	public function getFoldersInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = FALSE, array $folderNameFilterCallbacks = array()) {
		return $this->getDirectoryItemList($folderIdentifier, $start, $numberOfItems, $folderNameFilterCallbacks, FALSE, TRUE, $recursive);
	}

	/**
	 * Returns a list with the names of all files and folders in a path, optionally recursive.
	 *
	 * @param string $path The absolute path
	 * @param boolean $recursive If TRUE, recursively fetches files and folders
	 * @param boolean $includeFiles
	 * @param boolean $includeDirs
	 * @return array
	 */
	protected function retrieveFileAndFoldersInPath($path, $recursive = FALSE, $includeFiles = TRUE, $includeDirs = TRUE) {
		$pathLength = strlen($this->getAbsoluteBasePath());
		$iteratorMode = \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO;
		if ($recursive) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($path, $iteratorMode),
				\RecursiveIteratorIterator::SELF_FIRST
			);
		} else {
			$iterator = new \RecursiveDirectoryIterator($path, $iteratorMode);
		}

		$directoryEntries = array();
		while ($iterator->valid()) {
			/** @var $entry \SplFileInfo */
			$entry = $iterator->current();
			// skip non-files/non-folders, and empty entries
			if ((!$entry->isFile() && !$entry->isDir()) || $entry->getFilename() == '' ||
				($entry->isFile() && !$includeFiles) || ($entry->isDir() && !$includeDirs)) {
				$iterator->next();
				continue;
			}
			$entryIdentifier = '/' . substr($entry->getPathname(), $pathLength);
			$entryName = PathUtility::basename($entryIdentifier);
			if ($entry->isDir()) {
				$entryIdentifier .= '/';
			}
			$entryArray = array(
				'identifier' => $entryIdentifier,
				'name' => $entryName,
				'type' => $entry->isDir() ? 'dir' : 'file'
			);
			$directoryEntries[$entryIdentifier] = $entryArray;
			$iterator->next();
		}
		return $directoryEntries;
	}

	/**
	 * Extracts information about a file from the filesystem.
	 *
	 * @param string $filePath The absolute path to the file
	 * @param string $containerPath The relative path to the file's container
	 * @param array $propertiesToExtract array of properties which should be returned, if empty all will be extracted
	 * @return array
	 */
	protected function extractFileInformation($filePath, $containerPath, array $propertiesToExtract = array()) {
		if (count($propertiesToExtract) === 0) {
			$propertiesToExtract = array(
				'size', 'atime', 'atime', 'mtime', 'ctime', 'mimetype', 'name',
				'identifier', 'identifier_hash', 'storage', 'folder_hash'
			);
		}
		$fileInformation = array();
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
	public function getSpecificFileInformation($fileIdentifier, $containerPath, $property) {
		$identifier = $this->canonicalizeAndCheckFileIdentifier($containerPath . PathUtility::basename($fileIdentifier));

		switch ($property) {
			case 'size':
				return filesize($fileIdentifier);
			case 'atime':
				return fileatime($fileIdentifier);
			case 'mtime':
				return filemtime($fileIdentifier);
			case 'ctime':
				return filectime($fileIdentifier);
			case 'name':
				return PathUtility::basename($fileIdentifier);
			case 'mimetype':
				return $this->getMimeTypeOfFile($fileIdentifier);
			case 'identifier':
				return $identifier;
			case 'storage':
				return $this->storageUid;
			case 'identifier_hash':
				return $this->hashIdentifier($identifier);
			case 'folder_hash':
				return $this->hashIdentifier($this->getParentFolderIdentifierOfIdentifier($identifier));
			default:
				throw new \InvalidArgumentException(sprintf('The information "%s" is not available.', $property));
		}
		return NULL;
	}

	/**
	 * Returns the absolute path of the folder this driver operates on.
	 *
	 * @return string
	 */
	protected function getAbsoluteBasePath() {
		return $this->absoluteBasePath;
	}

	/**
	 * Returns the absolute path of a file or folder.
	 *
	 * @param string $fileIdentifier
	 * @return string
	 */
	protected function getAbsolutePath($fileIdentifier) {
		$relativeFilePath = ltrim($this->canonicalizeAndCheckFileIdentifier($fileIdentifier), '/');
		$path = $this->absoluteBasePath . $relativeFilePath;
		return $path;
	}

	/**
	 * Get MIME type of file.
	 *
	 * @param string $absoluteFilePath Absolute path to file
	 * @return string|boolean MIME type. eg, text/html, FALSE on error
	 */
	protected function getMimeTypeOfFile($absoluteFilePath) {
		if (function_exists('finfo_file')) {
			$fileInfo = new \finfo();
			return $fileInfo->file($absoluteFilePath, FILEINFO_MIME_TYPE);
		} elseif (function_exists('mime_content_type')) {
			return mime_content_type($absoluteFilePath);
		}
		return FALSE;
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
	public function hash($fileIdentifier, $hashAlgorithm) {
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
	 * @param string $localFilePath (within PATH_site)
	 * @param string $targetFolderIdentifier
	 * @param string $newFileName optional, if not given original name is used
	 * @param boolean $removeOriginal if set the original file will be removed after successful operation
	 * @return string the identifier of the new file
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = TRUE) {
		$localFilePath = $this->canonicalizeAndCheckFilePath($localFilePath);
		// as for the "virtual storage" for backwards-compatibility, this check always fails, as the file probably lies under PATH_site
		// thus, it is not checked here
		// @ todo is check in storage
		if (GeneralUtility::isFirstPartOfStr($localFilePath, $this->absoluteBasePath) && $this->storageUid > 0) {
			throw new \InvalidArgumentException('Cannot add a file that is already part of this storage.', 1314778269);
		}
		$newFileName = $this->sanitizeFileName($newFileName !== '' ? $newFileName : PathUtility::basename($localFilePath));
		$newFileIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier) . $newFileName;
		$targetPath = $this->absoluteBasePath . $newFileIdentifier;

		if ($removeOriginal) {
			if (is_uploaded_file($localFilePath)) {
				$result = move_uploaded_file($localFilePath, $targetPath);
			} else {
				$result = rename($localFilePath, $targetPath);
			}
		} else {
			$result = copy($localFilePath, $targetPath);
		}
		if ($result === FALSE || !file_exists($targetPath)) {
			throw new \RuntimeException('Adding file ' . $localFilePath . ' at ' . $newFileIdentifier . ' failed.');
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
	 * @return boolean
	 */
	public function fileExists($fileIdentifier) {
		$absoluteFilePath = $this->getAbsolutePath($fileIdentifier);
		return is_file($absoluteFilePath);
	}

	/**
	 * Checks if a file inside a folder exists
	 *
	 * @param string $fileName
	 * @param string $folderIdentifier
	 * @return boolean
	 */
	public function fileExistsInFolder($fileName, $folderIdentifier) {
		$identifier = $folderIdentifier . '/' . $fileName;
		$identifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
		return $this->fileExists($identifier);
	}

	/**
	 * Checks if a folder exists.
	 *
	 * @param string $folderIdentifier
	 *
	 * @return boolean
	 */
	public function folderExists($folderIdentifier) {
		$absoluteFilePath = $this->getAbsolutePath($folderIdentifier);
		return is_dir($absoluteFilePath);
	}

	/**
	 * Checks if a folder inside a folder exists.
	 *
	 * @param string $folderName
	 * @param string $folderIdentifier
	 * @return boolean
	 */
	public function folderExistsInFolder($folderName, $folderIdentifier) {
		$identifier = $folderIdentifier . '/' . $folderName;
		$identifier = $this->canonicalizeAndCheckFolderIdentifier($identifier);
		return $this->folderExists($identifier);
	}

	/**
	 * Returns the Identifier for a folder within a given folder.
	 *
	 * @param string $folderName The name of the target folder
	 * @param string $folderIdentifier
	 *
	 * @return string
	 */
	public function getFolderInFolder($folderName, $folderIdentifier) {
		$folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier . '/' . $folderName);
		return $folderIdentifier;
	}

	/**
	 * Replaces the contents (and file-specific metadata) of a file object with a local file.
	 *
	 * @param string $fileIdentifier
	 * @param string $localFilePath
	 * @return boolean TRUE if the operation succeeded
	 * @throws \RuntimeException
	 */
	public function replaceFile($fileIdentifier, $localFilePath) {
		$filePath = $this->getAbsolutePath($fileIdentifier);
		$result = rename($localFilePath, $filePath);
		if ($result === FALSE) {
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
	public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName) {
		$sourcePath = $this->getAbsolutePath($fileIdentifier);
		$newIdentifier = $targetFolderIdentifier . '/' . $fileName;
		$newIdentifier = $this->canonicalizeAndCheckFileIdentifier($newIdentifier);

		copy($sourcePath, $this->absoluteBasePath . $newIdentifier);
		GeneralUtility::fixPermissions($this->absoluteBasePath . $newIdentifier);
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
	 *
	 * @return string
	 * @throws \RuntimeException
	 */
	public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName) {
		$sourcePath = $this->getAbsolutePath($fileIdentifier);
		$targetIdentifier = $targetFolderIdentifier . '/' . $newFileName;
		$targetIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetIdentifier);
		$result = rename($sourcePath, $this->getAbsolutePath($targetIdentifier));
		if ($result === FALSE) {
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
	protected function copyFileToTemporaryPath($fileIdentifier) {
		$sourcePath = $this->getAbsolutePath($fileIdentifier);
		$temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
		$result = copy($sourcePath, $temporaryPath);
		touch($temporaryPath, filemtime($sourcePath));
		if ($result === FALSE) {
			throw new \RuntimeException('Copying file ' . $fileIdentifier . ' to temporary path failed.', 1320577649);
		}
		return $temporaryPath;
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
	 * @throws \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException
	 */
	protected function createIdentifierMap(array $filesAndFolders, $sourceFolderIdentifier, $targetFolderIdentifier) {
		$identifierMap = array();
		$identifierMap[$sourceFolderIdentifier] = $targetFolderIdentifier;
		foreach ($filesAndFolders as $oldItem) {
			if ($oldItem['type'] == 'dir') {
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
				throw new \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException(
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
	public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName) {
		$sourcePath = $this->getAbsolutePath($sourceFolderIdentifier);
		$relativeTargetPath = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);
		$targetPath = $this->getAbsolutePath($relativeTargetPath);
		// get all files and folders we are going to move, to have a map for updating later.
		$filesAndFolders = $this->retrieveFileAndFoldersInPath($sourcePath, TRUE);
		$result = rename($sourcePath, $targetPath);
		if ($result === FALSE) {
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
	 * @return boolean
	 * @throws \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException
	 */
	public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName) {
		// This target folder path already includes the topmost level, i.e. the folder this method knows as $folderToCopy.
		// We can thus rely on this folder being present and just create the subfolder we want to copy to.
		$newFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);
		$sourceFolderPath = $this->getAbsolutePath($sourceFolderIdentifier);
		$targetFolderPath = $this->getAbsolutePath($newFolderIdentifier);

		mkdir($targetFolderPath);
		/** @var $iterator \RecursiveDirectoryIterator */
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($sourceFolderPath),
			\RecursiveIteratorIterator::SELF_FIRST
		);
		// Rewind the iterator as this is important for some systems e.g. Windows
		$iterator->rewind();
		while ($iterator->valid()) {
			/** @var $current \RecursiveDirectoryIterator */
			$current = $iterator->current();
			$fileName = $current->getFilename();
			$itemSubPath = GeneralUtility::fixWindowsFilePath($iterator->getSubPathname());
			if ($current->isDir() && !($fileName === '..' || $fileName === '.')) {
				GeneralUtility::mkdir($targetFolderPath . '/' . $itemSubPath);
			} elseif ($current->isFile()) {
				$result = copy($sourceFolderPath . '/' . $itemSubPath, $targetFolderPath . '/' . $itemSubPath);
				if ($result === FALSE) {
					// rollback
					GeneralUtility::rmdir($targetFolderIdentifier, TRUE);
					throw new \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException(
						'Copying file "' . $sourceFolderPath . $itemSubPath . '" to "' . $targetFolderPath . $itemSubPath . '" failed.',
						1330119452
					);

				}
			}
			$iterator->next();
		}
		GeneralUtility::fixPermissions($targetFolderPath, TRUE);
		return TRUE;
	}

	/**
	 * Renames a file in this storage.
	 *
	 * @param string $fileIdentifier
	 * @param string $newName The target path (including the file name!)
	 * @return string The identifier of the file after renaming
	 * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
	 * @throws \RuntimeException
	 */
	public function renameFile($fileIdentifier, $newName) {
		// Makes sure the Path given as parameter is valid
		$newName = $this->sanitizeFileName($newName);
		$newIdentifier = rtrim(GeneralUtility::fixWindowsFilePath(PathUtility::dirname($fileIdentifier)), '/') . '/' . $newName;
		$newIdentifier = $this->canonicalizeAndCheckFileIdentifier($newIdentifier);
		// The target should not exist already
		if ($this->fileExists($newIdentifier)) {
			throw new \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException('The target file already exists.', 1320291063);
		}
		$sourcePath = $this->getAbsolutePath($fileIdentifier);
		$targetPath = $this->getAbsolutePath($newIdentifier);
		$result = rename($sourcePath, $targetPath);
		if ($result === FALSE) {
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
	public function renameFolder($folderIdentifier, $newName) {
		$folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
		$newName = $this->sanitizeFileName($newName);

		$newIdentifier = PathUtility::dirname($folderIdentifier) . '/' . $newName;
		$newIdentifier = $this->canonicalizeAndCheckFolderIdentifier($newIdentifier);

		$sourcePath = $this->getAbsolutePath($folderIdentifier);
		$targetPath = $this->getAbsolutePath($newIdentifier);
		// get all files and folders we are going to move, to have a map for updating later.
		$filesAndFolders = $this->retrieveFileAndFoldersInPath($sourcePath, TRUE);
		$result = rename($sourcePath, $targetPath);
		if ($result === FALSE) {
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
					$sourcePath, $targetPath, $e->getMessage()
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
	 * @return boolean TRUE if deleting the file succeeded
	 * @throws \RuntimeException
	 */
	public function deleteFile($fileIdentifier) {
		$filePath = $this->getAbsolutePath($fileIdentifier);
		$result = unlink($filePath);
		if ($result === FALSE) {
			throw new \RuntimeException('Deletion of file ' . $fileIdentifier . ' failed.', 1320855304);
		}
		return $result;
	}

	/**
	 * Removes a folder from this storage.
	 *
	 * @param string $folderIdentifier
	 * @param boolean $deleteRecursively
	 * @return boolean
	 * @throws \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException
	 */
	public function deleteFolder($folderIdentifier, $deleteRecursively = FALSE) {
		$folderPath = $this->getAbsolutePath($folderIdentifier);
		$result = GeneralUtility::rmdir($folderPath, $deleteRecursively);
		if ($result === FALSE) {
			throw new \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException(
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
	 * @return boolean TRUE if there are no files and folders within $folder
	 */
	public function isFolderEmpty($folderIdentifier) {
		$path = $this->getAbsolutePath($folderIdentifier);
		$dirHandle = opendir($path);
		while ($entry = readdir($dirHandle)) {
			if ($entry !== '.' && $entry !== '..') {
				closedir($dirHandle);
				return FALSE;
			}
		}
		closedir($dirHandle);
		return TRUE;
	}

	/**
	 * Returns (a local copy of) a file for processing it. This makes a copy
	 * first when in writable mode, so if you change the file, you have to update it yourself afterwards.
	 *
	 * @param string $fileIdentifier
	 * @param boolean $writable Set this to FALSE if you only need the file for read operations.
	 *                          This might speed up things, e.g. by using a cached local version.
	 *                          Never modify the file if you have set this flag!
	 * @return string The path to the file on the local disk
	 */
	public function getFileForLocalProcessing($fileIdentifier, $writable = TRUE) {
		if ($writable === FALSE) {
			return $this->getAbsolutePath($fileIdentifier);
		} else {
			return $this->copyFileToTemporaryPath($fileIdentifier);
		}
	}


	/**
	 * Returns the permissions of a file/folder as an array (keys r, w) of boolean flags
	 *
	 * @param string $identifier
	 * @return array
	 * @throws \RuntimeException
	 */
	public function getPermissions($identifier) {
		$path = $this->getAbsolutePath($identifier);
		$permissionBits = fileperms($path);
		if ($permissionBits === FALSE) {
			throw new \RuntimeException('Error while fetching permissions for ' . $path, 1319455097);
		}
		return array(
			'r' => (bool)is_readable($path),
			'w' => (bool)is_writable($path)
		);
	}

	/**
	 * Checks if a given identifier is within a container, e.g. if
	 * a file or folder is within another folder. It will also return
	 * TRUE if both canonicalized identifiers are equal.
	 *
	 * @param string $folderIdentifier
	 * @param string $identifier identifier to be checked against $folderIdentifier
	 * @return boolean TRUE if $content is within or matches $folderIdentifier
	 */
	public function isWithin($folderIdentifier, $identifier) {
		$folderIdentifier = $this->canonicalizeAndCheckFileIdentifier($folderIdentifier);
		$entryIdentifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
		if ($folderIdentifier === $entryIdentifier) {
			return TRUE;
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
	 * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException
	 * @throws \RuntimeException
	 */
	public function createFile($fileName, $parentFolderIdentifier) {
		if (!$this->isValidFilename($fileName)) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException(
				'Invalid characters in fileName "' . $fileName . '"',
				1320572272
			);
		}
		$parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
		$fileIdentifier =  $this->canonicalizeAndCheckFileIdentifier(
			$parentFolderIdentifier . $this->sanitizeFileName(ltrim($fileName, '/'))
		);
		$absoluteFilePath = $this->getAbsolutePath($fileIdentifier);
		$result = touch($absoluteFilePath);
		GeneralUtility::fixPermissions($absoluteFilePath);
		clearstatcache();
		if ($result !== TRUE) {
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
	public function getFileContents($fileIdentifier) {
		$filePath = $this->getAbsolutePath($fileIdentifier);
		return file_get_contents($filePath);
	}

	/**
	 * Sets the contents of a file to the specified value.
	 *
	 * @param string $fileIdentifier
	 * @param string $contents
	 * @return integer The number of bytes written to the file
	 * @throws \RuntimeException if the operation failed
	 */
	public function setFileContents($fileIdentifier, $contents) {
		$filePath = $this->getAbsolutePath($fileIdentifier);
		$result = file_put_contents($filePath, $contents);

		// Make sure later calls to filesize() etc. return correct values.
		clearstatcache(TRUE, $filePath);

		if ($result === FALSE) {
			throw new \RuntimeException('Setting contents of file "' . $fileIdentifier . '" failed.', 1325419305);
		}
		return $result;
	}

	/**
	 * Gets the charset conversion object.
	 *
	 * @return \TYPO3\CMS\Core\Charset\CharsetConverter
	 */
	protected function getCharsetConversion() {
		if (!isset($this->charsetConversion)) {
			if (TYPO3_MODE === 'FE') {
				$this->charsetConversion = $GLOBALS['TSFE']->csConvObj;
			} elseif (is_object($GLOBALS['LANG'])) {
				// BE assumed:
				$this->charsetConversion = $GLOBALS['LANG']->csConvObj;
			} else {
				// The object may not exist yet, so we need to create it now. Happens in the Install Tool for example.
				$this->charsetConversion = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
			}
		}
		return $this->charsetConversion;
	}

	/**
	 * Returns the role of an item (currently only folders; can later be extended for files as well)
	 *
	 * @param string $folderIdentifier
	 * @return string
	 */
	public function getRole($folderIdentifier) {
		$name = PathUtility::basename($folderIdentifier);
		$role = $this->mappingFolderNameToRole[$name];
		if (empty($role)) {
			$role = FolderInterface::ROLE_DEFAULT;
		}
		return $role;
	}

	/**
	 * Directly output the contents of the file to the output
	 * buffer. Should not take care of header files or flushing
	 * buffer before. Will be taken care of by the Storage.
	 *
	 * @param string $identifier
	 *
	 * @return void
	 */
	public function dumpFileContents($identifier) {
		readfile($this->getAbsolutePath($this->canonicalizeAndCheckFileIdentifier($identifier)), 0);
	}


}
