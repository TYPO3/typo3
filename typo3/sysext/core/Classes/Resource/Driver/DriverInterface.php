<?php
namespace TYPO3\CMS\Core\Resource\Driver;

/***************************************************************
 * Copyright notice
 *
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


/**
 * An interface Drivers have to implement to fulfil the needs
 * of the FAL API.
 */
interface DriverInterface {

	/**
	 * Processes the configuration for this driver.
	 * @return void
	 */
	public function processConfiguration();

	/**
	 * Sets the storage uid the driver belongs to
	 *
	 * @param integer $storageUid
	 * @return void
	 */
	public function setStorageUid($storageUid);

	/**
	 * Initializes this object. This is called by the storage after the driver
	 * has been attached.
	 *
	 * @return void
	 */
	public function initialize();

	/**
	 * Returns the capabilities of this driver.
	 *
	 * @return integer
	 * @see Storage::CAPABILITY_* constants
	 */
	public function getCapabilities();

	/**
	 * Merges the capabilites merged by the user at the storage
	 * configuration into the actual capabilities of the driver
	 * and returns the result.
	 *
	 * @param integer $capabilities
	 *
	 * @return integer
	 */
	public function mergeConfigurationCapabilities($capabilities);

	/**
	 * Returns TRUE if this driver has the given capability.
	 *
	 * @param integer $capability A capability, as defined in a CAPABILITY_* constant
	 * @return boolean
	 */
	public function hasCapability($capability);

	/**
	 * Returns TRUE if this driver uses case-sensitive identifiers. NOTE: This
	 * is a configurable setting, but the setting does not change the way the
	 * underlying file system treats the identifiers; the setting should
	 * therefore always reflect the file system and not try to change its
	 * behaviour
	 *
	 * @return boolean
	 */
	public function isCaseSensitiveFileSystem();

	/**
	 * Cleans a fileName from not allowed characters
	 *
	 * @param string $fileName
	 * @param string $charset Charset of the a fileName
	 *                        (defaults to current charset; depending on context)
	 * @return string the cleaned filename
	 */
	public function sanitizeFileName($fileName, $charset = '');

	/**
	 * Hashes a file identifier, taking the case sensitivity of the file system
	 * into account. This helps mitigating problems with case-insensitive
	 * databases.
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function hashIdentifier($identifier);

	/**
	 * Returns the identifier of the root level folder of the storage.
	 *
	 * @return string
	 */
	public function getRootLevelFolder();

	/**
	 * Returns the identifier of the default folder new files should be put into.
	 *
	 * @return string
	 */
	public function getDefaultFolder();

	/**
	 * Returns the identifier of the folder the file resides in
	 *
	 * @param string $fileIdentifier
	 *
	 * @return string
	 */
	public function getParentFolderIdentifierOfIdentifier($fileIdentifier);

	/**
	 * Returns the public URL to a file.
	 * Either fully qualified URL or relative to PATH_site (rawurlencoded).
	 *
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function getPublicUrl($identifier);

	/**
	 * Creates a folder, within a parent folder.
	 * If no parent folder is given, a root level folder will be created
	 *
	 * @param string $newFolderName
	 * @param string $parentFolderIdentifier
	 * @param boolean $recursive
	 * @return string the Identifier of the new folder
	 */
	public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = FALSE);

	/**
	 * Renames a folder in this storage.
	 *
	 * @param string $folderIdentifier
	 * @param string $newName
	 * @return array A map of old to new file identifiers of all affected resources
	 */
	public function renameFolder($folderIdentifier, $newName);

	/**
	 * Removes a folder in filesystem.
	 *
	 * @param string $folderIdentifier
	 * @param boolean $deleteRecursively
	 * @return boolean
	 */
	public function deleteFolder($folderIdentifier, $deleteRecursively = FALSE);

	/**
	 * Checks if a file exists.
	 *
	 * @param string $fileIdentifier
	 *
	 * @return boolean
	 */
	public function fileExists($fileIdentifier);

	/**
	 * Checks if a folder exists.
	 *
	 * @param string $folderIdentifier
	 *
	 * @return boolean
	 */
	public function folderExists($folderIdentifier);

	/**
	 * Checks if a folder contains files and (if supported) other folders.
	 *
	 * @param string $folderIdentifier
	 * @return boolean TRUE if there are no files and folders within $folder
	 */
	public function isFolderEmpty($folderIdentifier);

	/**
	 * Adds a file from the local server hard disk to a given path in TYPO3s
	 * virtual file system. This assumes that the local file exists, so no
	 * further check is done here! After a successful the original file must
	 * not exist anymore.
	 *
	 * @param string $localFilePath (within PATH_site)
	 * @param string $targetFolderIdentifier
	 * @param string $newFileName optional, if not given original name is used
	 * @param boolean $removeOriginal if set the original file will be removed
	 *                                after successful operation
	 * @return string the identifier of the new file
	 */
	public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = TRUE);

	/**
	 * Creates a new (empty) file and returns the identifier.
	 *
	 * @param string $fileName
	 * @param string $parentFolderIdentifier
	 * @return string
	 */
	public function createFile($fileName, $parentFolderIdentifier);

	/**
	 * Copies a file *within* the current storage.
	 * Note that this is only about an inner storage copy action,
	 * where a file is just copied to another folder in the same storage.
	 *
	 * @param string $fileIdentifier
	 * @param string $targetFolderIdentifier
	 * @param string $fileName
	 * @return string the Identifier of the new file
	 */
	public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName);

	/**
	 * Renames a file in this storage.
	 *
	 * @param string $fileIdentifier
	 * @param string $newName The target path (including the file name!)
	 * @return string The identifier of the file after renaming
	 */
	public function renameFile($fileIdentifier, $newName);

	/**
	 * Replaces a file with file in local file system.
	 *
	 * @param string $fileIdentifier
	 * @param string $localFilePath
	 * @return boolean TRUE if the operation succeeded
	 */
	public function replaceFile($fileIdentifier, $localFilePath);

	/**
	 * Removes a file from the filesystem. This does not check if the file is
	 * still used or if it is a bad idea to delete it for some other reason
	 * this has to be taken care of in the upper layers (e.g. the Storage)!
	 *
	 * @param string $fileIdentifier
	 * @return boolean TRUE if deleting the file succeeded
	 */
	public function deleteFile($fileIdentifier);

	/**
	 * Creates a hash for a file.
	 *
	 * @param string $fileIdentifier
	 * @param string $hashAlgorithm The hash algorithm to use
	 * @return string
	 */
	public function hash($fileIdentifier, $hashAlgorithm);


	/**
	 * Moves a file *within* the current storage.
	 * Note that this is only about an inner-storage move action,
	 * where a file is just moved to another folder in the same storage.
	 *
	 * @param string $fileIdentifier
	 * @param string $targetFolderIdentifier
	 * @param string $newFileName
	 *
	 * @return string
	 */
	public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName);


	/**
	 * Folder equivalent to moveFileWithinStorage().
	 *
	 * @param string $sourceFolderIdentifier
	 * @param string $targetFolderIdentifier
	 * @param string $newFolderName
	 *
	 * @return array All files which are affected, map of old => new file identifiers
	 */
	public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName);

	/**
	 * Folder equivalent to copyFileWithinStorage().
	 *
	 * @param string $sourceFolderIdentifier
	 * @param string $targetFolderIdentifier
	 * @param string $newFolderName
	 *
	 * @return boolean
	 */
	public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName);

	/**
	 * Returns the contents of a file. Beware that this requires to load the
	 * complete file into memory and also may require fetching the file from an
	 * external location. So this might be an expensive operation (both in terms
	 * of processing resources and money) for large files.
	 *
	 * @param string $fileIdentifier
	 * @return string The file contents
	 */
	public function getFileContents($fileIdentifier);

	/**
	 * Sets the contents of a file to the specified value.
	 *
	 * @param string $fileIdentifier
	 * @param string $contents
	 * @return integer The number of bytes written to the file
	 */
	public function setFileContents($fileIdentifier, $contents);

	/**
	 * Checks if a file inside a folder exists
	 *
	 * @param string $fileName
	 * @param string $folderIdentifier
	 * @return boolean
	 */
	public function fileExistsInFolder($fileName, $folderIdentifier);

	/**
	 * Checks if a folder inside a folder exists.
	 *
	 * @param string $folderName
	 * @param string $folderIdentifier
	 * @return boolean
	 */
	public function folderExistsInFolder($folderName, $folderIdentifier);

	/**
	 * Returns a path to a local copy of a file for processing it. When changing the
	 * file, you have to take care of replacing the current version yourself!
	 *
	 * @param string $fileIdentifier
	 * @param bool $writable Set this to FALSE if you only need the file for read
	 *                       operations. This might speed up things, e.g. by using
	 *                       a cached local version. Never modify the file if you
	 *                       have set this flag!
	 * @return string The path to the file on the local disk
	 */
	public function getFileForLocalProcessing($fileIdentifier, $writable = TRUE);

	/**
	 * Returns the permissions of a file/folder as an array
	 * (keys r, w) of boolean flags
	 *
	 * @param string $identifier
	 * @return array
	 */
	public function getPermissions($identifier);

	/**
	 * Directly output the contents of the file to the output
	 * buffer. Should not take care of header files or flushing
	 * buffer before. Will be taken care of by the Storage.
	 *
	 * @param string $identifier
	 * @return void
	 */
	public function dumpFileContents($identifier);

	/**
	 * Checks if a given identifier is within a container, e.g. if
	 * a file or folder is within another folder.
	 * This can e.g. be used to check for web-mounts.
	 *
	 * Hint: this also needs to return TRUE if the given identifier
	 * matches the container identifier to allow access to the root
	 * folder of a filemount.
	 *
	 * @param string $folderIdentifier
	 * @param string $identifier identifier to be checked against $folderIdentifier
	 * @return boolean TRUE if $content is within or matches $folderIdentifier
	 */
	public function isWithin($folderIdentifier, $identifier);

	/**
	 * Returns information about a file.
	 *
	 * @param string $fileIdentifier
	 * @param array $propertiesToExtract Array of properties which are be extracted
	 *                                   If empty all will be extracted
	 * @return array
	 */
	public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = array());

	/**
	 * Returns information about a file.
	 *
	 * @param string $folderIdentifier
	 * @return array
	 */
	public function getFolderInfoByIdentifier($folderIdentifier);

	/**
	 * Returns a list of files inside the specified path
	 *
	 * @param string $folderIdentifier
	 * @param integer $start
	 * @param integer $numberOfItems
	 * @param boolean $recursive
	 * @param array $filenameFilterCallbacks callbacks for filtering the items
	 *
	 * @return array of FileIdentifiers
	 */
	public function getFilesInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = FALSE, array $filenameFilterCallbacks = array());

	/**
	 * Returns a list of folders inside the specified path
	 *
	 * @param string $folderIdentifier
	 * @param integer $start
	 * @param integer $numberOfItems
	 * @param boolean $recursive
	 * @param array $folderNameFilterCallbacks callbacks for filtering the items
	 *
	 * @return array of Folder Identifier
	 */
	public function getFoldersInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = FALSE, array $folderNameFilterCallbacks = array());

}
