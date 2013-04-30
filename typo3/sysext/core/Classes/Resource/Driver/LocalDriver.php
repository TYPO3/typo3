<?php
namespace TYPO3\CMS\Core\Resource\Driver;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
 * (c) 2013 Stefan Neufeind <info (at) speedpartner.de>
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
 * A copy is found in the textfile GPL.txt and important notices to the license
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
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class LocalDriver extends \TYPO3\CMS\Core\Resource\Driver\AbstractDriver {

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
	protected $baseUri;

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
	 * Checks if a configuration is valid for this storage.
	 *
	 * @param array $configuration The configuration
	 * @return void
	 * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException
	 */
	static public function verifyConfiguration(array $configuration) {
		self::calculateBasePath($configuration);
	}

	/**
	 * Processes the configuration for this driver.
	 *
	 * @return void
	 */
	public function processConfiguration() {
		$this->absoluteBasePath = $this->calculateBasePath($this->configuration);
	}

	/**
	 * Initializes this object. This is called by the storage after the driver
	 * has been attached.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->determineBaseUrl();
		// The capabilities of this driver. See CAPABILITY_* constants for possible values
		$this->capabilities = \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_BROWSABLE | \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_PUBLIC | \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_WRITABLE;
	}

	/**
	 * Determines the base URL for this driver, from the configuration or
	 * the TypoScript frontend object
	 *
	 * @return void
	 */
	protected function determineBaseUrl() {
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($this->absoluteBasePath, PATH_site)) {
			// use site-relative URLs
			// TODO add unit test
			$this->baseUri = substr($this->absoluteBasePath, strlen(PATH_site));
		} elseif (isset($this->configuration['baseUri']) && \TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($this->configuration['baseUri'])) {
			$this->baseUri = rtrim($this->configuration['baseUri'], '/') . '/';
		} else {

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
			throw new \TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException('Configuration must contain base path.', 1346510477);
		}

		if ($configuration['pathType'] === 'relative') {
			$relativeBasePath = $configuration['basePath'];
			$absoluteBasePath = PATH_site . $relativeBasePath;
		} else {
			$absoluteBasePath = $configuration['basePath'];
		}
		$absoluteBasePath = rtrim($absoluteBasePath, '/') . '/';
		if (!is_dir($absoluteBasePath)) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException('Base path "' . $absoluteBasePath . '" does not exist or is no directory.', 1299233097);
		}
		return $absoluteBasePath;
	}

	/**
	 * Returns the public URL to a file. For the local driver, this will always
	 * return a path relative to PATH_site.
	 *
	 * @param \TYPO3\CMS\Core\Resource\ResourceInterface  $fileOrFolder
	 * @param bool $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 * @return string
	 */
	public function getPublicUrl(\TYPO3\CMS\Core\Resource\ResourceInterface $fileOrFolder, $relativeToCurrentScript = FALSE) {
		if ($this->configuration['pathType'] === 'relative' && rtrim($this->configuration['basePath'], '/') !== '') {
			$publicUrl = rtrim($this->configuration['basePath'], '/') . '/' . ltrim($fileOrFolder->getIdentifier(), '/');
		} elseif (isset($this->baseUri)) {
			$publicUrl = $this->baseUri . ltrim($fileOrFolder->getIdentifier(), '/');
		} else {
			throw new \TYPO3\CMS\Core\Resource\Exception('Public URL of file cannot be determined', 1329765518);
		}
		// If requested, make the path relative to the current script in order to make it possible
		// to use the relative file
		if ($relativeToCurrentScript) {
			$publicUrl = PathUtility::getRelativePathTo(PathUtility::dirname((PATH_site . $publicUrl))) . PathUtility::basename($publicUrl);
		}
		return $publicUrl;
	}

	/**
	 * Returns the root level folder of the storage.
	 *
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	public function getRootLevelFolder() {
		if (!$this->rootLevelFolder) {
			$this->rootLevelFolder = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->createFolderObject($this->storage, '/', '');
		}
		return $this->rootLevelFolder;
	}

	/**
	 * Returns the default folder new files should be put into.
	 *
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	public function getDefaultFolder() {
		if (!$this->defaultLevelFolder) {
			if (!file_exists(($this->absoluteBasePath . 'user_upload/'))) {
				mkdir($this->absoluteBasePath . 'user_upload/');
			}
			$this->defaultLevelFolder = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->createFolderObject($this->storage, '/user_upload/', '');
		}
		return $this->defaultLevelFolder;
	}

	/**
	 * Creates a folder.
	 *
	 * @param string $newFolderName
	 * @param \TYPO3\CMS\Core\Resource\Folder $parentFolder
	 * @return \TYPO3\CMS\Core\Resource\Folder The new (created) folder object
	 */
	public function createFolder($newFolderName, \TYPO3\CMS\Core\Resource\Folder $parentFolder) {
		$newFolderName = trim($this->sanitizeFileName($newFolderName), '/');
		$newFolderPath = $this->getAbsolutePath($parentFolder) . $newFolderName;
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($newFolderPath);
		return \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->createFolderObject($this->storage, $parentFolder->getIdentifier() . $newFolderName, $newFolderName);
	}

	/**
	 * Returns information about a file.
	 *
	 * @param string $fileIdentifier In the case of the LocalDriver, this is the (relative) path to the file.
	 * @return array
	 */
	public function getFileInfoByIdentifier($fileIdentifier) {
		// Makes sure the Path given as parameter is valid
		$this->checkFilePath($fileIdentifier);
		$dirPath = \TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath(
			PathUtility::dirname($fileIdentifier)
		);
		if ($dirPath === '' || $dirPath === '.') {
			$dirPath = '/';
		} elseif ($dirPath !== '/') {
			$dirPath = '/' . trim($dirPath, '/') . '/';
		}
		$absoluteFilePath = $this->absoluteBasePath . ltrim($fileIdentifier, '/');
		// don't use $this->fileExists() because we need the absolute path to the file anyways, so we can directly
		// use PHP's filesystem method.
		if (!file_exists($absoluteFilePath)) {
			throw new \InvalidArgumentException('File ' . $fileIdentifier . ' does not exist.', 1314516809);
		}
		return $this->extractFileInformation($absoluteFilePath, $dirPath);
	}

	/**
	 * Wrapper for \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr()
	 *
	 * @param string $theFile Filepath to evaluate
	 * @return boolean TRUE if no '/', '..' or '\' is in the $theFile
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr()
	 */
	protected function isPathValid($theFile) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr($theFile);
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
	 */
	protected function sanitizeFileName($fileName, $charset = '') {
		// Handle UTF-8 characters
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
			// Allow ".", "-", 0-9, a-z, A-Z and everything beyond U+C0 (latin capital letter a with grave)
			$cleanFileName = preg_replace('/[\\x00-\\x2C\\/\\x3A-\\x3F\\x5B-\\x60\\x7B-\\xBF]/u', '_', trim($fileName));
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
			$cleanFileName = preg_replace('/[^.[:alnum:]_-]/', '_', trim($fileName));
		}
		// Strip trailing dots and return
		$cleanFileName = preg_replace('/\\.*$/', '', $cleanFileName);
		if (!$cleanFileName) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException('File name ' . $cleanFileName . ' is invalid.', 1320288991);
		}
		return $cleanFileName;
	}

	/**
	 * Generic wrapper for extracting a list of items from a path. The
	 * extraction itself is done by the given handler method
	 *
	 * @param string $path
	 * @param integer $start The position to start the listing; if not set, start from the beginning
	 * @param integer $numberOfItems The number of items to list; if set to zero, all items are returned
	 * @param array $filterMethods The filter methods used to filter the directory items
	 * @param string $itemHandlerMethod The method (in this class) that handles the single iterator elements.
	 * @param array $itemRows
	 * @param boolean $recursive
	 * @return array
	 */
	// TODO add unit tests
	protected function getDirectoryItemList($path, $start, $numberOfItems, array $filterMethods, $itemHandlerMethod, $itemRows = array(), $recursive = FALSE) {
		$realPath = rtrim(($this->absoluteBasePath . trim($path, '/')), '/') . '/';
		if (!is_dir($realPath)) {
			throw new \InvalidArgumentException('Cannot list items in directory ' . $path . ' - does not exist or is no directory', 1314349666);
		}
		if ($start > 0) {
			$start--;
		}
		// Fetch the files and folders and sort them by name; we have to do
		// this here because the directory iterator does return them in
		// an arbitrary order
		$items = $this->getFileAndFoldernamesInPath($realPath, $recursive);
		natcasesort($items);
		$iterator = new \ArrayIterator($items);
		if ($iterator->count() == 0) {
			return array();
		}
		$iterator->seek($start);
		if ($path !== '' && $path !== '/') {
			$path = '/' . trim($path, '/') . '/';
		}
		// $c is the counter for how many items we still have to fetch (-1 is unlimited)
		$c = $numberOfItems > 0 ? $numberOfItems : -1;
		$items = array();
		while ($iterator->valid() && ($numberOfItems == 0 || $c > 0)) {
			// $iteratorItem is the file or folder name
			$iteratorItem = $iterator->current();
			// go on to the next iterator item now as we might skip this one early
			$iterator->next();
			$identifier = $path . $iteratorItem;
			if ($this->applyFilterMethodsToDirectoryItem($filterMethods, $iteratorItem, $identifier, $path) === FALSE) {
				continue;
			}
			if (isset($itemRows[$identifier])) {
				list($key, $item) = $this->{$itemHandlerMethod}($iteratorItem, $path, $itemRows[$identifier]);
			} else {
				list($key, $item) = $this->{$itemHandlerMethod}($iteratorItem, $path);
			}
			if (empty($item)) {
				continue;
			}
			$items[$key] = $item;
			// Decrement item counter to make sure we only return $numberOfItems
			// we cannot do this earlier in the method (unlike moving the iterator forward) because we only add the
			// item here
			--$c;
		}
		return $items;
	}

	/**
	 * Handler for items in a file list.
	 *
	 * @param string $fileName
	 * @param string $path
	 * @param array $fileRow The pre-loaded file row
	 * @return array
	 */
	protected function getFileList_itemCallback($fileName, $path, array $fileRow = array()) {
		$filePath = $this->getAbsolutePath($path . $fileName);
		if (!is_file($filePath)) {
			return array('', array());
		}
		// TODO add unit test for existing file row case
		if (!empty($fileRow) && filemtime($filePath) <= $fileRow['modification_date']) {
			return array($fileName, $fileRow);
		} else {
			return array($fileName, $this->extractFileInformation($filePath, $path));
		}
	}

	/**
	 * Handler for items in a directory listing.
	 *
	 * @param string $folderName The folder's name
	 * @param string $parentPath The path to the folder's parent folder
	 * @param array $folderRow [optional]
	 * @return array
	 */
	protected function getFolderList_itemCallback($folderName, $parentPath, array $folderRow = array()) {
		$folderPath = $this->getAbsolutePath($parentPath . $folderName);
		if (!is_dir($folderPath)) {
			return array('', array());
		}
		// also don't show hidden files
		if ($folderName === '..' || $folderName === '.' || $folderName === '') {
			return array('', array());
		}
		// remove the trailing slash from the folder name (the trailing slash comes from the DirectoryIterator)
		$folderName = substr($folderName, 0, -1);
		return array($folderName, $this->extractFolderInformation($folderPath, $parentPath));
	}

	/**
	 * Returns a list with the names of all files and folders in a path, optionally recursive.
	 * Folder names have a trailing slash.
	 *
	 * @param string $path The absolute path
	 * @param bool $recursive If TRUE, recursively fetches files and folders
	 * @return array
	 */
	protected function getFileAndFoldernamesInPath($path, $recursive = FALSE) {
		if ($recursive) {
			$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::CURRENT_AS_FILEINFO));
		} else {
			$iterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::CURRENT_AS_FILEINFO);
		}
		$directoryEntries = array();
		while ($iterator->valid()) {
			/** @var $entry \SplFileInfo */
			$entry = $iterator->current();
			// skip non-files/non-folders, and empty entries
			if (!$entry->isFile() && !$entry->isDir() || $entry->getFilename() == '') {
				$iterator->next();
				continue;
			}
			// skip the pseudo-directories "." and ".."
			if ($entry->getFilename() == '..' || $entry->getFilename() == '.') {
				$iterator->next();
				continue;
			}
			$entryPath = GeneralUtility::fixWindowsFilePath(substr($entry->getPathname(), strlen($path)));
			if ($entry->isDir()) {
				$entryPath .= '/';
			}
			$directoryEntries[] = $entryPath;
			$iterator->next();
		}
		return $directoryEntries;
	}

	/**
	 * Extracts information about a file from the filesystem.
	 *
	 * @param string $filePath The absolute path to the file
	 * @param string $containerPath The relative path to the file's container
	 * @return array
	 */
	protected function extractFileInformation($filePath, $containerPath) {
		$fileName = PathUtility::basename($filePath);
		$fileInformation = array(
			'size' => filesize($filePath),
			'atime' => fileatime($filePath),
			'mtime' => filemtime($filePath),
			'ctime' => filectime($filePath),
			'mimetype' => $this->getMimeTypeOfFile($filePath),
			'name' => $fileName,
			'identifier' => $containerPath . $fileName,
			'storage' => $this->storage->getUid()
		);
		return $fileInformation;
	}

	/**
	 * Extracts information about a folder from the filesystem.
	 *
	 * @param string $folderPath The absolute path to the folder
	 * @param string $containerPath The relative path to the folder's container inside the storage (must end with a trailing slash)
	 * @return array
	 */
	protected function extractFolderInformation($folderPath, $containerPath) {
		$folderName = PathUtility::basename($folderPath);
		$folderInformation = array(
			'ctime' => filectime($folderPath),
			'mtime' => filemtime($folderPath),
			'name' => $folderName,
			'identifier' => $containerPath . $folderName . '/',
			'storage' => $this->storage->getUid()
		);
		return $folderInformation;
	}

	/**
	 * Returns the absolute path of the folder this driver operates on.
	 *
	 * @return string
	 */
	public function getAbsoluteBasePath() {
		return $this->absoluteBasePath;
	}

	/**
	 * Returns the absolute path of a file or folder.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface|\TYPO3\CMS\Core\Resource\Folder|string $file
	 * @return string
	 */
	public function getAbsolutePath($file) {
		if ($file instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
			$path = $this->absoluteBasePath . ltrim($file->getIdentifier(), '/');
		} elseif ($file instanceof \TYPO3\CMS\Core\Resource\Folder) {
			// We can assume a trailing slash here because it is added by the folder object on construction.
			$path = $this->absoluteBasePath . ltrim($file->getIdentifier(), '/');
		} elseif (is_string($file)) {
			$path = $this->absoluteBasePath . ltrim($file, '/');
		} else {
			throw new \RuntimeException('Type "' . gettype($file) . '" is not supported.', 1325191178);
		}
		return $path;
	}

	/**
	 * Returns metadata of a file (size, times, mimetype)
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return array
	 */
	public function getLowLevelFileInfo(\TYPO3\CMS\Core\Resource\FileInterface $file) {
		// TODO define which data should be returned
		// TODO write unit test
		// TODO cache this info. Registry?
		// TODO merge with extractFolderInformation() above?!
		$filePath = $this->getAbsolutePath($file);
		$fileStat = stat($filePath);
		$mimeType = $this->getMimeTypeOfFile($filePath);
		$stat = array(
			'size' => filesize($filePath),
			'atime' => $fileStat['atime'],
			'mtime' => $fileStat['mtime'],
			'ctime' => $fileStat['ctime'],
			'nlink' => $fileStat['nlink'],
			'type' => $mimeType,
			'mimetype' => $mimeType
		);
		return $stat;
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
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param string $hashAlgorithm The hash algorithm to use
	 * @return string
	 */
	public function hash(\TYPO3\CMS\Core\Resource\FileInterface $file, $hashAlgorithm) {
		if (!in_array($hashAlgorithm, $this->getSupportedHashAlgorithms())) {
			throw new \InvalidArgumentException('Hash algorithm "' . $hashAlgorithm . '" is not supported.', 1304964032);
		}
		switch ($hashAlgorithm) {
		case 'sha1':
			$hash = sha1_file($this->getAbsolutePath($file));
			break;
		case 'md5':
			$hash = md5_file($this->getAbsolutePath($file));
			break;
		default:
			throw new \RuntimeException('Hash algorithm ' . $hashAlgorithm . ' is not implemented.', 1329644451);
		}
		return $hash;
	}

	/**
	 * Adds a file from the local server hard disk to a given path in TYPO3s virtual file system.
	 *
	 * This assumes that the local file exists, so no further check is done here!
	 *
	 * @param string $localFilePath
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $fileName The name to add the file under
	 * @param \TYPO3\CMS\Core\Resource\AbstractFile $updateFileObject File object to update (instead of creating a new object). With this parameter, this function can be used to "populate" a dummy file object with a real file underneath.
	 * @todo \TYPO3\CMS\Core\Resource\File $updateFileObject should be \TYPO3\CMS\Core\Resource\FileInterface, but indexer logic is only in \TYPO3\CMS\Core\Resource\File
	 * @return \TYPO3\CMS\Core\Resource\FileInterface
	 */
	public function addFile($localFilePath, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $fileName, \TYPO3\CMS\Core\Resource\AbstractFile $updateFileObject = NULL) {
		// as for the "virtual storage" for backwards-compatibility, this check always fails, as the file probably lies under PATH_site
		// thus, it is not checked here
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($localFilePath, $this->absoluteBasePath) && $this->storage->getUid() > 0) {
			throw new \InvalidArgumentException('Cannot add a file that is already part of this storage.', 1314778269);
		}
		$relativeTargetPath = ltrim($targetFolder->getIdentifier(), '/');
		$relativeTargetPath .= $this->sanitizeFileName($fileName ? $fileName : PathUtility::basename($localFilePath));
		$targetPath = $this->absoluteBasePath . $relativeTargetPath;
		if (is_uploaded_file($localFilePath)) {
			$moveResult = move_uploaded_file($localFilePath, $targetPath);
		} else {
			$moveResult = rename($localFilePath, $targetPath);
		}
		if ($moveResult !== TRUE) {
			throw new \RuntimeException('Moving file ' . $localFilePath . ' to ' . $targetPath . ' failed.', 1314803096);
		}
		clearstatcache();
		// Change the permissions of the file
		\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($targetPath);
		$fileInfo = $this->getFileInfoByIdentifier($relativeTargetPath);
		if ($updateFileObject) {
			$updateFileObject->updateProperties($fileInfo);
			return $updateFileObject;
		} else {
			$fileObject = $this->getFileObject($fileInfo);
			return $fileObject;
		}
	}

	/**
	 * Checks if a resource exists - does not care for the type (file or folder).
	 *
	 * @param $identifier
	 * @return boolean
	 */
	public function resourceExists($identifier) {
		$absoluteResourcePath = $this->absoluteBasePath . ltrim($identifier, '/');
		return file_exists($absoluteResourcePath);
	}

	/**
	 * Checks if a file exists.
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function fileExists($identifier) {
		$absoluteFilePath = $this->absoluteBasePath . ltrim($identifier, '/');
		return is_file($absoluteFilePath);
	}

	/**
	 * Checks if a file inside a storage folder exists
	 *
	 * @param string $fileName
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return boolean
	 */
	public function fileExistsInFolder($fileName, \TYPO3\CMS\Core\Resource\Folder $folder) {
		$identifier = ltrim($folder->getIdentifier(), '/') . $fileName;
		return $this->fileExists($identifier);
	}

	/**
	 * Checks if a folder exists.
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function folderExists($identifier) {
		$absoluteFilePath = $this->absoluteBasePath . ltrim($identifier, '/');
		return is_dir($absoluteFilePath);
	}

	/**
	 * Checks if a file inside a storage folder exists.
	 *
	 * @param string $folderName
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return boolean
	 */
	public function folderExistsInFolder($folderName, \TYPO3\CMS\Core\Resource\Folder $folder) {
		$identifier = $folder->getIdentifier() . $folderName;
		return $this->folderExists($identifier);
	}

	/**
	 * Returns a folder within the given folder.
	 *
	 * @param string $name The name of the folder to get
	 * @param \TYPO3\CMS\Core\Resource\Folder $parentFolder
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	public function getFolderInFolder($name, \TYPO3\CMS\Core\Resource\Folder $parentFolder) {
		$folderIdentifier = $parentFolder->getIdentifier() . $name . '/';
		return $this->getFolder($folderIdentifier);
	}

	/**
	 * Replaces the contents (and file-specific metadata) of a file object with a local file.
	 *
	 * @param \TYPO3\CMS\Core\Resource\AbstractFile $file
	 * @param string $localFilePath
	 * @return boolean TRUE if the operation succeeded
	 */
	public function replaceFile(\TYPO3\CMS\Core\Resource\AbstractFile $file, $localFilePath) {
		$filePath = $this->getAbsolutePath($file);
		$result = rename($localFilePath, $filePath);
		if ($result === FALSE) {
			throw new \RuntimeException('Replacing file ' . $filePath . ' with ' . $localFilePath . ' failed.', 1315314711);
		}
		$fileInfo = $this->getFileInfoByIdentifier($file->getIdentifier());
		$file->updateProperties($fileInfo);
		// TODO update index
		return $result;
	}

	/**
	 * Adds a file at the specified location. This should only be used internally.
	 *
	 * @param string $localFilePath
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $targetFileName
	 * @return boolean TRUE if adding the file succeeded
	 */
	public function addFileRaw($localFilePath, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $targetFileName) {
		$fileIdentifier = $targetFolder->getIdentifier() . $targetFileName;
		$absoluteFilePath = $this->absoluteBasePath . $fileIdentifier;
		$result = copy($localFilePath, $absoluteFilePath);
		if ($result === FALSE || !file_exists($absoluteFilePath)) {
			throw new \RuntimeException('Adding file ' . $localFilePath . ' at ' . $fileIdentifier . ' failed.');
		}
		return $fileIdentifier;
	}

	/**
	 * Deletes a file without access and usage checks. This should only be used internally.
	 *
	 * This accepts an identifier instead of an object because we might want to delete files that have no object
	 * associated with (or we don't want to create an object for) them - e.g. when moving a file to another storage.
	 *
	 * @param string $identifier
	 * @return bool TRUE if removing the file succeeded
	 */
	public function deleteFileRaw($identifier) {
		$targetPath = $this->absoluteBasePath . ltrim($identifier, '/');
		$result = unlink($targetPath);
		if ($result === FALSE || file_exists($targetPath)) {
			throw new \RuntimeException('Deleting file ' . $identifier . ' failed.', 1320381534);
		}
		return TRUE;
	}

	/**
	 * Copies a file *within* the current storage.
	 * Note that this is only about an intra-storage move action, where a file is just
	 * moved to another folder in the same storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $fileName
	 * @return \TYPO3\CMS\Core\Resource\FileInterface The new (copied) file object.
	 */
	public function copyFileWithinStorage(\TYPO3\CMS\Core\Resource\FileInterface $file, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $fileName) {
		// TODO add unit test
		$sourcePath = $this->getAbsolutePath($file);
		$targetPath = ltrim($targetFolder->getIdentifier(), '/') . $fileName;
		copy($sourcePath, $this->absoluteBasePath . $targetPath);
		return $this->getFile($targetPath);
	}

	/**
	 * Moves a file *within* the current storage.
	 * Note that this is only about an intra-storage move action, where a file is just
	 * moved to another folder in the same storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $fileName
	 * @return boolean
	 */
	public function moveFileWithinStorage(\TYPO3\CMS\Core\Resource\FileInterface $file, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $fileName) {
		$sourcePath = $this->getAbsolutePath($file);
		$targetIdentifier = $targetFolder->getIdentifier() . $fileName;
		$result = rename($sourcePath, $this->absoluteBasePath . $targetIdentifier);
		if ($result === FALSE) {
			throw new \RuntimeException('Moving file ' . $sourcePath . ' to ' . $targetIdentifier . ' failed.', 1315314712);
		}
		return $targetIdentifier;
	}

	/**
	 * Copies a file to a temporary path and returns that path.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return string The temporary path
	 */
	public function copyFileToTemporaryPath(\TYPO3\CMS\Core\Resource\FileInterface $file) {
		$sourcePath = $this->getAbsolutePath($file);
		$temporaryPath = $this->getTemporaryPathForFile($file);
		$result = copy($sourcePath, $temporaryPath);
		if ($result === FALSE) {
			throw new \RuntimeException('Copying file ' . $file->getIdentifier() . ' to temporary path failed.', 1320577649);
		}
		return $temporaryPath;
	}

	/**
	 * Creates a map of old and new file/folder identifiers after renaming or
	 * moving a folder. The old identifier is used as the key, the new one as the value.
	 *
	 * @param array $filesAndFolders
	 * @param string $relativeSourcePath
	 * @param string $relativeTargetPath
	 * @return array
	 */
	protected function createIdentifierMap(array $filesAndFolders, $relativeSourcePath, $relativeTargetPath) {
		$identifierMap = array();
		$identifierMap[$relativeSourcePath] = $relativeTargetPath;
		foreach ($filesAndFolders as $oldSubIdentifier) {
			$oldIdentifier = $relativeSourcePath . $oldSubIdentifier;
			$newIdentifier = $relativeTargetPath . $oldSubIdentifier;
			if (!$this->resourceExists($newIdentifier)) {
				throw new \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException(sprintf('File "%1$s" was not found (should have been copied/moved from "%2$s").', $newIdentifier, $oldIdentifier), 1330119453);
			}
			$identifierMap[$oldIdentifier] = $newIdentifier;
		}
		return $identifierMap;
	}

	/**
	 * Folder equivalent to moveFileWithinStorage().
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderToMove
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $newFolderName
	 * @return array A map of old to new file identifiers
	 */
	public function moveFolderWithinStorage(\TYPO3\CMS\Core\Resource\Folder $folderToMove, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $newFolderName) {
		$relativeSourcePath = $folderToMove->getIdentifier();
		$sourcePath = $this->getAbsolutePath($relativeSourcePath);
		$relativeTargetPath = $targetFolder->getIdentifier() . $newFolderName . '/';
		$targetPath = $this->getAbsolutePath($relativeTargetPath);
		// get all files and folders we are going to move, to have a map for updating later.
		$filesAndFolders = $this->getFileAndFoldernamesInPath($sourcePath, TRUE);
		$result = rename($sourcePath, $targetPath);
		if ($result === FALSE) {
			throw new \RuntimeException('Moving folder ' . $sourcePath . ' to ' . $targetPath . ' failed.', 1320711817);
		}
		// Create a mapping from old to new identifiers
		$identifierMap = $this->createIdentifierMap($filesAndFolders, $relativeSourcePath, $relativeTargetPath);
		return $identifierMap;
	}

	/**
	 * Folder equivalent to copyFileWithinStorage().
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderToCopy
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $newFolderName
	 * @return boolean
	 */
	public function copyFolderWithinStorage(\TYPO3\CMS\Core\Resource\Folder $folderToCopy, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $newFolderName) {
		// This target folder path already includes the topmost level, i.e. the folder this method knows as $folderToCopy.
		// We can thus rely on this folder being present and just create the subfolder we want to copy to.
		$targetFolderPath = $this->getAbsolutePath($targetFolder) . $newFolderName . '/';
		mkdir($targetFolderPath);
		$sourceFolderPath = $this->getAbsolutePath($folderToCopy);
		/** @var $iterator RecursiveDirectoryIterator */
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceFolderPath));
		while ($iterator->valid()) {
			/** @var $current RecursiveDirectoryIterator */
			$current = $iterator->current();
			$itemSubPath = $iterator->getSubPathname();
			if ($current->isDir() && !($itemSubPath === '..' || $itemSubPath === '.')) {
				mkdir($targetFolderPath . $itemSubPath);
			} elseif ($current->isFile()) {
				$result = copy($sourceFolderPath . $itemSubPath, $targetFolderPath . $itemSubPath);
				if ($result === FALSE) {
					throw new \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException('Copying file "' . $sourceFolderPath . $itemSubPath . '" to "' . $targetFolderPath . $itemSubPath . '" failed.', 1330119452);
				}
			}
			$iterator->next();
		}
		return TRUE;
	}

	/**
	 * Move a folder from another storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderToMove
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetParentFolder
	 * @param string $newFolderName
	 * @return boolean
	 */
	public function moveFolderBetweenStorages(\TYPO3\CMS\Core\Resource\Folder $folderToMove, \TYPO3\CMS\Core\Resource\Folder $targetParentFolder, $newFolderName) {
		// TODO implement a clever shortcut here if both storages are of type local
		return parent::moveFolderBetweenStorages($folderToMove, $targetParentFolder, $newFolderName);
	}

	/**
	 * Copy a folder from another storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderToCopy
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetParentFolder
	 * @param string $newFolderName
	 * @return boolean
	 */
	public function copyFolderBetweenStorages(\TYPO3\CMS\Core\Resource\Folder $folderToCopy, \TYPO3\CMS\Core\Resource\Folder $targetParentFolder, $newFolderName) {
		// TODO implement a clever shortcut here if both storages are of type local
		return parent::copyFolderBetweenStorages($folderToCopy, $targetParentFolder, $newFolderName);
	}

	/**
	 * Renames a file in this storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param string $newName The target path (including the file name!)
	 * @return string The identifier of the file after renaming
	 */
	public function renameFile(\TYPO3\CMS\Core\Resource\FileInterface $file, $newName) {
		// Makes sure the Path given as parameter is valid
		$newName = $this->sanitizeFileName($newName);
		$newIdentifier = rtrim(GeneralUtility::fixWindowsFilePath(PathUtility::dirname($file->getIdentifier())), '/') . '/' . $newName;
		// The target should not exist already
		if ($this->fileExists($newIdentifier)) {
			throw new \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException('The target file already exists.', 1320291063);
		}
		$sourcePath = $this->getAbsolutePath($file);
		$targetPath = $this->absoluteBasePath . '/' . ltrim($newIdentifier, '/');
		$result = rename($sourcePath, $targetPath);
		if ($result === FALSE) {
			throw new \RuntimeException('Renaming file ' . $sourcePath . ' to ' . $targetPath . ' failed.', 1320375115);
		}
		return $newIdentifier;
	}

	/**
	 * Makes sure the Path given as parameter is valid
	 *
	 * @param string $filePath The file path (including the file name!)
	 * @return void
	 */
	protected function checkFilePath($filePath) {
		// filePath must be valid
		if (!$this->isPathValid($filePath)) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InvalidPathException('File ' . $filePath . ' is not valid (".." and "//" is not allowed in path).', 1320286857);
		}
	}

	/**
	 * Renames a folder in this storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @param string $newName The target path (including the file name!)
	 * @return array A map of old to new file identifiers
	 * @throws \RuntimeException if renaming the folder failed
	 */
	public function renameFolder(\TYPO3\CMS\Core\Resource\Folder $folder, $newName) {
		// Makes sure the path given as parameter is valid
		$newName = $this->sanitizeFileName($newName);
		$relativeSourcePath = $folder->getIdentifier();
		$sourcePath = $this->getAbsolutePath($relativeSourcePath);
		$relativeTargetPath = rtrim(GeneralUtility::fixWindowsFilePath(PathUtility::dirname($relativeSourcePath)), '/') . '/' . $newName . '/';
		$targetPath = $this->getAbsolutePath($relativeTargetPath);
		// get all files and folders we are going to move, to have a map for updating later.
		$filesAndFolders = $this->getFileAndFoldernamesInPath($sourcePath, TRUE);
		$result = rename($sourcePath, $targetPath);
		if ($result === FALSE) {
			throw new \RuntimeException(sprintf('Renaming folder "%1$s" to "%2$s" failed."', $sourcePath, $targetPath), 1320375116);
		}
		try {
			// Create a mapping from old to new identifiers
			$identifierMap = $this->createIdentifierMap($filesAndFolders, $relativeSourcePath, $relativeTargetPath);
		} catch (\Exception $e) {
			rename($targetPath, $sourcePath);
			throw new \RuntimeException(sprintf('Creating filename mapping after renaming "%1$s" to "%2$s" failed. Reverted rename operation.\\n\\nOriginal error: %3$s"', $sourcePath, $targetPath, $e->getMessage()), 1334160746);
		}
		return $identifierMap;
	}

	/**
	 * Removes a file from this storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return boolean TRUE if deleting the file succeeded
	 * @throws \RuntimeException
	 */
	public function deleteFile(\TYPO3\CMS\Core\Resource\FileInterface $file) {
		$filePath = $this->getAbsolutePath($file);
		$result = unlink($filePath);
		if ($result === FALSE) {
			throw new \RuntimeException('Deletion of file ' . $file->getIdentifier() . ' failed.', 1320855304);
		}
		return $result;
	}

	/**
	 * Removes a folder from this storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @param bool $deleteRecursively
	 * @return boolean
	 */
	public function deleteFolder(\TYPO3\CMS\Core\Resource\Folder $folder, $deleteRecursively = FALSE) {
		$folderPath = $this->getAbsolutePath($folder);
		$result = \TYPO3\CMS\Core\Utility\GeneralUtility::rmdir($folderPath, $deleteRecursively);
		if ($result === FALSE) {
			throw new \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException('Deleting folder "' . $folder->getIdentifier() . '" failed.', 1330119451);
		}
		return $result;
	}

	/**
	 * Checks if a folder contains files and (if supported) other folders.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return boolean TRUE if there are no files and folders within $folder
	 */
	public function isFolderEmpty(\TYPO3\CMS\Core\Resource\Folder $folder) {
		$path = $this->getAbsolutePath($folder);
		$dirHandle = opendir($path);
		while ($entry = readdir($dirHandle)) {
			if ($entry !== '.' && $entry !== '..') {
				closedir($dirHandle);
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Returns a (local copy of) a file for processing it. This makes a copy
	 * first when in writable mode, so if you change the file,
	 * you have to update it yourself afterwards.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param boolean $writable Set this to FALSE if you only need the file for read operations. This might speed up things, e.g. by using a cached local version. Never modify the file if you have set this flag!
	 * @return string The path to the file on the local disk
	 */
	public function getFileForLocalProcessing(\TYPO3\CMS\Core\Resource\FileInterface $file, $writable = TRUE) {
		if ($writable === FALSE) {
			// TODO check if this is ok or introduce additional measures against file changes
			return $this->getAbsolutePath($file);
		} else {
			// TODO check if this might also serve as a dump basic implementation in the abstract driver.
			return $this->copyFileToTemporaryPath($file);
		}
	}

	/**
	 * Returns the permissions of a file as an array (keys r, w) of boolean flags
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file The file object to check
	 * @return array
	 * @throws \RuntimeException If fetching the permissions failed
	 */
	public function getFilePermissions(\TYPO3\CMS\Core\Resource\FileInterface $file) {
		$filePath = $this->getAbsolutePath($file);
		return $this->getPermissions($filePath);
	}

	/**
	 * Returns the permissions of a folder as an array (keys r, w) of boolean flags
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return array
	 * @throws \RuntimeException If fetching the permissions failed
	 */
	public function getFolderPermissions(\TYPO3\CMS\Core\Resource\Folder $folder) {
		$folderPath = $this->getAbsolutePath($folder);
		return $this->getPermissions($folderPath);
	}

	/**
	 * Helper function to unify access to permission information
	 *
	 * @param string $path
	 * @return array
	 * @throws \RuntimeException If fetching the permissions failed
	 */
	protected function getPermissions($path) {
		$permissionBits = fileperms($path);
		if ($permissionBits === FALSE) {
			throw new \RuntimeException('Error while fetching permissions for ' . $path, 1319455097);
		}
		return array(
			'r' => (bool) is_readable($path),
			'w' => (bool) is_writable($path)
		);
	}

	/**
	 * Checks if a given object or identifier is within a container, e.g. if
	 * a file or folder is within another folder.
	 * This can e.g. be used to check for webmounts.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $container
	 * @param mixed $content An object or an identifier to check
	 * @return bool TRUE if $content is within $container, always FALSE if $container is not within this storage
	 */
	public function isWithin(\TYPO3\CMS\Core\Resource\Folder $container, $content) {
		if ($container->getStorage() != $this->storage) {
			return FALSE;
		}
		if ($content instanceof \TYPO3\CMS\Core\Resource\FileInterface || $content instanceof \TYPO3\CMS\Core\Resource\Folder) {
			$content = $container->getIdentifier();
		}
		$folderPath = $container->getIdentifier();
		$content = '/' . ltrim($content, '/');
		return \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($content, $folderPath);
	}

	/**
	 * Creates a new file and returns the matching file object for it.
	 *
	 * @param string $fileName
	 * @param \TYPO3\CMS\Core\Resource\Folder $parentFolder
	 * @return \TYPO3\CMS\Core\Resource\File
	 */
	public function createFile($fileName, \TYPO3\CMS\Core\Resource\Folder $parentFolder) {
		if (!$this->isValidFilename($fileName)) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException('Invalid characters in fileName "' . $fileName . '"', 1320572272);
		}
		$filePath = $parentFolder->getIdentifier() . $this->sanitizeFileName(ltrim($fileName, '/'));
		// TODO set permissions of new file
		$result = touch($this->absoluteBasePath . $filePath);
		clearstatcache();
		if ($result !== TRUE) {
			throw new \RuntimeException('Creating file ' . $filePath . ' failed.', 1320569854);
		}
		$fileInfo = $this->getFileInfoByIdentifier($filePath);
		return $this->getFileObject($fileInfo);
	}

	/**
	 * Returns the contents of a file. Beware that this requires to load the
	 * complete file into memory and also may require fetching the file from an
	 * external location. So this might be an expensive operation (both in terms of
	 * processing resources and money) for large files.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return string The file contents
	 */
	public function getFileContents(\TYPO3\CMS\Core\Resource\FileInterface $file) {
		$filePath = $this->getAbsolutePath($file);
		return file_get_contents($filePath);
	}

	/**
	 * Sets the contents of a file to the specified value.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param string $contents
	 * @return integer The number of bytes written to the file
	 * @throws \RuntimeException if the operation failed
	 */
	public function setFileContents(\TYPO3\CMS\Core\Resource\FileInterface $file, $contents) {
		$filePath = $this->getAbsolutePath($file);
		$result = file_put_contents($filePath, $contents);
		if ($result === FALSE) {
			throw new \RuntimeException('Setting contents of file "' . $file->getIdentifier() . '" failed.', 1325419305);
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
				$this->charsetConversion = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
			}
		}
		return $this->charsetConversion;
	}


	/**
	 * Returns the role of an item (currently only folders; can later be extended for files as well)
	 *
	 * @param \TYPO3\CMS\Core\Resource\ResourceInterface $item
	 * @return string
	 */
	public function getRole(\TYPO3\CMS\Core\Resource\ResourceInterface $item) {
		$role = $this->mappingFolderNameToRole[$item->getName()];
		if (empty($role)) {
			$role = FolderInterface::ROLE_DEFAULT;
		}
		return $role;
	}

}

?>