<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Andreas Wolf <andreas.wolf@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * A "mount point" inside the TYPO3 file handling.
 *
 * A "storage" object handles
 * - abstraction to the driver
 * - permissions (from the driver, and from the user, + capabilities)
 * - an entry point for files, folders, and for most other operations
 *
 * == Driver entry point
 * The driver itself, that does the actual work on the file system,
 * is inside the storage but completely shadowed by
 * the storage, as the storage also handles the abstraction to the
 * driver
 *
 * The storage can be on the local system, but can also be on a remote
 * system. The combination of driver + configurable capabilities (storage
 * is read-only e.g.) allows for flexible uses.
 *
 *
 * == Permission system
 * As all requests have to run through the storage, the storage knows about the
 * permissions of a BE/FE user, the file permissions / limitations of the driver
 * and has some configurable capabilities.
 * Additionally, a BE user can use "filemounts" (known from previous installations)
 * to limit his/her work-zone to only a subset (identifier and its subfolders/subfolders)
 * of the user itself.
 *
 * Check 1: "User Permissions" [is the user allowed to write a file) [is the user allowed to write a file]
 * Check 2: "File Mounts" of the User (act as subsets / filters to the identifiers) [is the user allowed to do something in this folder?]
 * Check 3: "Capabilities" of Storage (then: of Driver) [is the storage/driver writable?]
 * Check 4: "File permissions" of the Driver [is the folder writable?]
 *
 * @author Andreas Wolf <andreas.wolf@typo3.org>
 * @author Ingmar Schlecht <ingmar@typo3.org>
 */
class ResourceStorage implements ResourceStorageInterface {

	/**
	 * The storage driver instance belonging to this storage.
	 *
	 * @var Driver\DriverInterface
	 */
	protected $driver;

	/**
	 * The database record for this storage
	 *
	 * @var array
	 */
	protected $storageRecord;

	/**
	 * The configuration belonging to this storage (decoded from the configuration field).
	 *
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var Service\FileProcessingService
	 */
	protected $fileProcessingService;

	/**
	 * Whether to check if file or folder is in user mounts
	 * and the action is allowed for a user
	 * Default is FALSE so that resources are accessible for
	 * front end rendering or admins.
	 *
	 * @var boolean
	 */
	protected $evaluatePermissions = FALSE;

	/**
	 * User filemounts, added as an array, and used as filters
	 *
	 * @var array
	 */
	protected $fileMounts = array();

	/**
	 * The file permissions of the user (and their group) merged together and
	 * available as an array
	 *
	 * @var array
	 */
	protected $userPermissions = array();

	/**
	 * The capabilities of this storage as defined in the storage record.
	 * Also see the CAPABILITY_* constants below
	 *
	 * @var int
	 */
	protected $capabilities;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @var Folder
	 */
	protected $processingFolder;

	/**
	 * whether this storage is online or offline in this request
	 *
	 * @var boolean
	 */
	protected $isOnline = NULL;

	/**
	 * @var boolean
	 */
	protected $isDefault = FALSE;

	/**
	 * The filters used for the files and folder names.
	 *
	 * @var array
	 */
	protected $fileAndFolderNameFilters = array();

	/**
	 * Constructor for a storage object.
	 *
	 * @param Driver\DriverInterface $driver
	 * @param array $storageRecord The storage record row from the database
	 */
	public function __construct(Driver\DriverInterface $driver, array $storageRecord) {
		$this->storageRecord = $storageRecord;
		$this->configuration = ResourceFactory::getInstance()->convertFlexFormDataToConfigurationArray($storageRecord['configuration']);
		$this->capabilities =
			($this->storageRecord['is_browsable'] ? self::CAPABILITY_BROWSABLE : 0) |
			($this->storageRecord['is_public'] ? self::CAPABILITY_PUBLIC : 0) |
			($this->storageRecord['is_writable'] ? self::CAPABILITY_WRITABLE : 0);

		$this->driver = $driver;
		$this->driver->setStorageUid($storageRecord['uid']);
		$this->driver->mergeConfigurationCapabilities($this->capabilities);
		try {
			$this->driver->processConfiguration();
		} catch (Exception\InvalidConfigurationException $e) {
			// configuration error
			// mark this storage as permanently unusable
			$this->markAsPermanentlyOffline();
		}
		$this->driver->initialize();
		$this->capabilities = $this->driver->getCapabilities();

		$this->isDefault = (isset($storageRecord['is_default']) && $storageRecord['is_default'] == 1);
		$this->resetFileAndFolderNameFiltersToDefault();
	}

	/**
	 * Gets the configuration.
	 *
	 * @return array
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * Sets the configuration.
	 *
	 * @param array $configuration
	 */
	public function setConfiguration(array $configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * Gets the storage record.
	 *
	 * @return array
	 */
	public function getStorageRecord() {
		return $this->storageRecord;
	}

	/**
	 * Sets the storage that belongs to this storage.
	 *
	 * @param Driver\DriverInterface $driver
	 * @return ResourceStorage
	 */
	public function setDriver(Driver\DriverInterface $driver) {
		$this->driver = $driver;
		return $this;
	}

	/**
	 * Returns the driver object belonging to this storage.
	 *
	 * @return Driver\DriverInterface
	 */
	protected function getDriver() {
		return $this->driver;
	}

	/**
	 * Deprecated function, don't use it. Will be removed in some later revision.
	 *
	 * @param string $identifier
	 *
	 * @throws \BadMethodCallException
	 */
	public function getFolderByIdentifier($identifier) {
		throw new \BadMethodCallException(
			'Function TYPO3\\CMS\\Core\\Resource\\ResourceStorage::getFolderByIdentifier() has been renamed to just getFolder(). Please fix the method call.',
			1333754514
		);
	}

	/**
	 * Deprecated function, don't use it. Will be removed in some later revision.
	 *
	 * @param string $identifier
	 *
	 * @throws \BadMethodCallException
	 */
	public function getFileByIdentifier($identifier) {
		throw new \BadMethodCallException(
			'Function TYPO3\\CMS\\Core\\Resource\\ResourceStorage::getFileByIdentifier() has been renamed to just getFileInfoByIdentifier(). ' . 'Please fix the method call.',
			1333754533
		);
	}

	/**
	 * Returns the name of this storage.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->storageRecord['name'];
	}

	/**
	 * Returns the UID of this storage.
	 *
	 * @return int
	 */
	public function getUid() {
		return (int)$this->storageRecord['uid'];
	}

	/**
	 * Tells whether there are children in this storage.
	 *
	 * @return bool
	 */
	public function hasChildren() {
		return TRUE;
	}

	/*********************************
	 * Capabilities
	 ********************************/
	/**
	 * Returns the capabilities of this storage.
	 *
	 * @return int
	 * @see CAPABILITY_* constants
	 */
	public function getCapabilities() {
		return (int)$this->capabilities;
	}

	/**
	 * Returns TRUE if this storage has the given capability.
	 *
	 * @param int $capability A capability, as defined in a CAPABILITY_* constant
	 * @return bool
	 */
	protected function hasCapability($capability) {
		return ($this->capabilities & $capability) == $capability;
	}

	/**
	 * Returns TRUE if this storage is publicly available. This is just a
	 * configuration option and does not mean that it really *is* public. OTOH
	 * a storage that is marked as not publicly available will trigger the file
	 * publishing mechanisms of TYPO3.
	 *
	 * @return bool
	 */
	public function isPublic() {
		return $this->hasCapability(self::CAPABILITY_PUBLIC);
	}

	/**
	 * Returns TRUE if this storage is writable. This is determined by the
	 * driver and the storage configuration; user permissions are not taken into account.
	 *
	 * @return bool
	 */
	public function isWritable() {
		return $this->hasCapability(self::CAPABILITY_WRITABLE);
	}

	/**
	 * Returns TRUE if this storage is browsable by a (backend) user of TYPO3.
	 *
	 * @return bool
	 */
	public function isBrowsable() {
		return $this->isOnline() && $this->hasCapability(self::CAPABILITY_BROWSABLE);
	}

	/**
	 * Returns TRUE if the identifiers used by this storage are case-sensitive.
	 *
	 * @return bool
	 */
	public function usesCaseSensitiveIdentifiers() {
		return $this->driver->isCaseSensitiveFileSystem();
	}

	/**
	 * Returns TRUE if this storage is browsable by a (backend) user of TYPO3.
	 *
	 * @return bool
	 */
	public function isOnline() {
		if ($this->isOnline === NULL) {
			if ($this->getUid() === 0) {
				$this->isOnline = TRUE;
			}
			// the storage is not marked as online for a longer time
			if ($this->storageRecord['is_online'] == 0) {
				$this->isOnline = FALSE;
			}
			if ($this->isOnline !== FALSE) {
				// all files are ALWAYS available in the frontend
				if (TYPO3_MODE === 'FE') {
					$this->isOnline = TRUE;
				} else {
					// check if the storage is disabled temporary for now
					$registryObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
					$offlineUntil = $registryObject->get('core', 'sys_file_storage-' . $this->getUid() . '-offline-until');
					if ($offlineUntil && $offlineUntil > time()) {
						$this->isOnline = FALSE;
					} else {
						$this->isOnline = TRUE;
					}
				}
			}
		}
		return $this->isOnline;
	}

	/**
	 * Blows the "fuse" and marks the storage as offline.
	 *
	 * Can only be modified by an admin.
	 *
	 * Typically, this is only done if the configuration is wrong.
	 *
	 * @return void
	 */
	public function markAsPermanentlyOffline() {
		if ($this->getUid() > 0) {
			// @todo: move this to the storage repository
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_storage', 'uid=' . (int)$this->getUid(), array('is_online' => 0));
		}
		$this->storageRecord['is_online'] = 0;
		$this->isOnline = FALSE;
	}

	/**
	 * Marks this storage as offline for the next 5 minutes.
	 *
	 * Non-permanent: This typically happens for remote storages
	 * that are "flaky" and not available all the time.
	 *
	 * @return void
	 */
	public function markAsTemporaryOffline() {
		$registryObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$registryObject->set('core', 'sys_file_storage-' . $this->getUid() . '-offline-until', time() + 60 * 5);
		$this->storageRecord['is_online'] = 0;
		$this->isOnline = FALSE;
	}

	/*********************************
	 * User Permissions / File Mounts
	 ********************************/
	/**
	 * Adds a filemount as a "filter" for users to only work on a subset of a
	 * storage object
	 *
	 * @param string $folderIdentifier
	 * @param array $additionalData
	 *
	 * @throws Exception\FolderDoesNotExistException
	 * @return void
	 */
	public function addFileMount($folderIdentifier, $additionalData = array()) {
		// check for the folder before we add it as a filemount
		if ($this->driver->folderExists($folderIdentifier) === FALSE) {
			// if there is an error, this is important and should be handled
			// as otherwise the user would see the whole storage without any restrictions for the filemounts
			throw new Exception\FolderDoesNotExistException('Folder for file mount ' . $folderIdentifier . ' does not exist.', 1334427099);
		}
		$data = $this->driver->getFolderInfoByIdentifier($folderIdentifier);
		$folderObject = ResourceFactory::getInstance()->createFolderObject($this, $data['identifier'], $data['name']);
		if (empty($additionalData)) {
			$additionalData = array(
				'path' => $folderIdentifier,
				'title' => $folderIdentifier,
				'folder' => $folderObject
			);
		} else {
			$additionalData['folder'] = $folderObject;
			if (!isset($additionalData['title'])) {
				$additionalData['title'] = $folderIdentifier;
			}
		}
		$this->fileMounts[$folderIdentifier] = $additionalData;
	}

	/**
	 * Returns all file mounts that are registered with this storage.
	 *
	 * @return array
	 */
	public function getFileMounts() {
		return $this->fileMounts;
	}

	/**
	 * Checks if the given subject is within one of the registered user
	 * filemounts. If not, working with the file is not permitted for the user.
	 *
	 * @param Folder $subject
	 * @return bool
	 */
	public function isWithinFileMountBoundaries($subject) {
		if (!$this->evaluatePermissions) {
			return TRUE;
		}
		$isWithinFilemount = FALSE;
		if (!$subject) {
			$subject = $this->getRootLevelFolder();
		}
		$identifier = $subject->getIdentifier();

		// Allow access to processing folder
		if ($this->driver->isWithin($this->getProcessingFolder()->getIdentifier(), $identifier)) {
			$isWithinFilemount = TRUE;
		} else {
			// Check if the identifier of the subject is within at
			// least one of the file mounts
			foreach ($this->fileMounts as $fileMount) {
				if ($this->driver->isWithin($fileMount['folder']->getIdentifier(), $identifier)) {
					$isWithinFilemount = TRUE;
					break;
				}
			}
		}
		return $isWithinFilemount;
	}

	/**
	 * Sets whether the permissions to access or write
	 * into this storage should be checked or not.
	 *
	 * @param boolean $evaluatePermissions
	 */
	public function setEvaluatePermissions($evaluatePermissions) {
		$this->evaluatePermissions = (bool)$evaluatePermissions;
	}

	/**
	 * Gets whether the permissions to access or write
	 * into this storage should be checked or not.
	 *
	 * @return bool $evaluatePermissions
	 */
	public function getEvaluatePermissions() {
		return $this->evaluatePermissions;
	}

	/**
	 * Sets the user permissions of the storage.
	 *
	 * @param array $userPermissions
	 * @return void
	 */
	public function setUserPermissions(array $userPermissions) {
		$this->userPermissions = $userPermissions;
	}

	/**
	 * Checks if the ACL settings allow for a certain action
	 * (is a user allowed to read a file or copy a folder).
	 *
	 * @param string $action
	 * @param string $type either File or Folder
	 * @return bool
	 */
	public function checkUserActionPermission($action, $type) {
		if (!$this->evaluatePermissions) {
			return TRUE;
		}

		$allow = FALSE;
		if (!empty($this->userPermissions[strtolower($action) . ucfirst(strtolower($type))])) {
			$allow = TRUE;
		}

		return $allow;
	}

	/**
	 * Checks if a file operation (= action) is allowed on a
	 * File/Folder/Storage (= subject).
	 *
	 * This method, by design, does not throw exceptions or do logging.
	 * Besides the usage from other methods in this class, it is also used by
	 * the File List UI to check whether an action is allowed and whether action
	 * related UI elements should thus be shown (move icon, edit icon, etc.)
	 *
	 * @param string $action action, can be read, write, delete
	 * @param FileInterface $file
	 * @return bool
	 */
	public function checkFileActionPermission($action, FileInterface $file) {
		$isProcessedFile = $file instanceof ProcessedFile;
		// Check 1: Does the user have permission to perform the action? e.g. "readFile"
		if (!$isProcessedFile && $this->checkUserActionPermission($action, 'File') === FALSE) {
			return FALSE;
		}
		// Check 2: No action allowed on files for denied file extensions
		if (!$this->checkFileExtensionPermission($file->getName())) {
			return FALSE;
		}
		// Check 3: Does the user have the right to perform the action?
		// (= is he within the file mount borders)
		if (!$isProcessedFile && !$this->isWithinFileMountBoundaries($file)) {
			return FALSE;
		}
		$isReadCheck = FALSE;
		if (in_array($action, array('read', 'copy', 'move'), TRUE)) {
			$isReadCheck = TRUE;
		}
		$isWriteCheck = FALSE;
		if (in_array($action, array('add', 'write', 'move', 'rename', 'unzip', 'delete'), TRUE)) {
			$isWriteCheck = TRUE;
		}

		$isMissing = FALSE;
		if (!$isProcessedFile && $file instanceof File) {
			$isMissing = $file->isMissing();
		}

		// Check 4: Check the capabilities of the storage (and the driver)
		if ($isWriteCheck && ($isMissing || !$this->isWritable())) {
			return FALSE;
		}
		// Check 5: "File permissions" of the driver (only when file isn't marked as missing)
		if (!$isMissing) {
			$filePermissions = $this->driver->getPermissions($file->getIdentifier());
			if ($isReadCheck && !$filePermissions['r']) {
				return FALSE;
			}
			if ($isWriteCheck && !$filePermissions['w']) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Checks if a folder operation (= action) is allowed on a Folder.
	 *
	 * This method, by design, does not throw exceptions or do logging.
	 * See the checkFileActionPermission() method above for the reasons.
	 *
	 * @param string $action
	 * @param Folder $folder
	 * @return bool
	 */
	public function checkFolderActionPermission($action, Folder $folder = NULL) {
		// Check 1: Does the user have permission to perform the action? e.g. "writeFolder"
		if ($this->checkUserActionPermission($action, 'Folder') === FALSE) {
			return FALSE;
		}

		// If we do not have a folder here, we cannot do further checks
		if ($folder === NULL) {
			return TRUE;
		}

		// Check 2: Does the user has the right to perform the action?
		// (= is he within the file mount borders)
		if (!$this->isWithinFileMountBoundaries($folder)) {
			return FALSE;
		}
		$isReadCheck = FALSE;
		if (in_array($action, array('read', 'copy'), TRUE)) {
			$isReadCheck = TRUE;
		}
		$isWriteCheck = FALSE;
		if (in_array($action, array('add', 'move', 'write', 'delete', 'rename'), TRUE)) {
			$isWriteCheck = TRUE;
		}
		// Check 3: Check the capabilities of the storage (and the driver)
		if ($isReadCheck && !$this->isBrowsable()) {
			return FALSE;
		}
		if ($isWriteCheck && !$this->isWritable()) {
			return FALSE;
		}
		// Check 4: "Folder permissions" of the driver
		$folderPermissions = $this->driver->getPermissions($folder->getIdentifier());
		if ($isReadCheck && !$folderPermissions['r']) {
			return FALSE;
		}
		if ($isWriteCheck && !$folderPermissions['w']) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * If the fileName is given, checks it against the
	 * TYPO3_CONF_VARS[BE][fileDenyPattern] + and if the file extension is allowed.
	 *
	 * @param string $fileName full filename
	 * @return bool TRUE if extension/filename is allowed
	 */
	protected function checkFileExtensionPermission($fileName) {
		if (!$this->evaluatePermissions) {
			return TRUE;
		}
		$fileName = $this->driver->sanitizeFileName($fileName);
		$isAllowed = GeneralUtility::verifyFilenameAgainstDenyPattern($fileName);
		if ($isAllowed) {
			$fileInfo = GeneralUtility::split_fileref($fileName);
			// Set up the permissions for the file extension
			$fileExtensionPermissions = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace'];
			$fileExtensionPermissions['allow'] = GeneralUtility::uniqueList(strtolower($fileExtensionPermissions['allow']));
			$fileExtensionPermissions['deny'] = GeneralUtility::uniqueList(strtolower($fileExtensionPermissions['deny']));
			$fileExtension = strtolower($fileInfo['fileext']);
			if ($fileExtension !== '') {
				// If the extension is found amongst the allowed types, we return TRUE immediately
				if ($fileExtensionPermissions['allow'] === '*' || GeneralUtility::inList($fileExtensionPermissions['allow'], $fileExtension)) {
					return TRUE;
				}
				// If the extension is found amongst the denied types, we return FALSE immediately
				if ($fileExtensionPermissions['deny'] === '*' || GeneralUtility::inList($fileExtensionPermissions['deny'], $fileExtension)) {
					return FALSE;
				}
				// If no match we return TRUE
				return TRUE;
			} else {
				if ($fileExtensionPermissions['allow'] === '*') {
					return TRUE;
				}
				if ($fileExtensionPermissions['deny'] === '*') {
					return FALSE;
				}
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Assures read permission for given folder.
	 *
	 * @param Folder $folder If a folder is given, mountpoints are checked. If not only user folder read permissions are checked.
	 * @return void
	 * @throws Exception\InsufficientFolderAccessPermissionsException
	 */
	protected function assureFolderReadPermission(Folder $folder = NULL) {
		if (!$this->checkFolderActionPermission('read', $folder)) {
			throw new Exception\InsufficientFolderAccessPermissionsException('You are not allowed to access the given folder', 1375955684);
		}
	}

	/**
	 * Assures delete permission for given folder.
	 *
	 * @param Folder $folder If a folder is given, mountpoints are checked. If not only user folder delete permissions are checked.
	 * @param boolean $checkDeleteRecursively
	 * @return void
	 * @throws Exception\InsufficientFolderAccessPermissionsException
	 * @throws Exception\InsufficientFolderWritePermissionsException
	 * @throws Exception\InsufficientUserPermissionsException
	 */
	protected function assureFolderDeletePermission(Folder $folder, $checkDeleteRecursively) {
		// Check user permissions for recursive deletion if it is requested
		if ($checkDeleteRecursively && !$this->checkUserActionPermission('recursivedelete', 'Folder')) {
			throw new Exception\InsufficientUserPermissionsException('You are not allowed to delete folders recursively', 1377779423);
		}
		// Check user action permission
		if (!$this->checkFolderActionPermission('delete', $folder)) {
			throw new Exception\InsufficientFolderAccessPermissionsException('You are not allowed to delete the given folder', 1377779039);
		}
		// Check if the user has write permissions to folders
		// Would be good if we could check for actual write permissions in the containig folder
		// but we cannot since we have no access to the containing folder of this file.
		if (!$this->checkUserActionPermission('write', 'Folder')) {
			throw new Exception\InsufficientFolderWritePermissionsException('Writing to folders is not allowed.', 1377779111);
		}
	}

	/**
	 * Assures read permission for given file.
	 *
	 * @param FileInterface $file
	 * @return void
	 * @throws Exception\InsufficientFileAccessPermissionsException
	 * @throws Exception\IllegalFileExtensionException
	 */
	protected function assureFileReadPermission(FileInterface $file) {
		if (!$this->checkFileActionPermission('read', $file)) {
			throw new Exception\InsufficientFileAccessPermissionsException('You are not allowed to access that file.', 1375955429);
		}
		if (!$this->checkFileExtensionPermission($file->getName())) {
			throw new Exception\IllegalFileExtensionException('You are not allowed to use that file extension', 1375955430);
		}
	}

	/**
	 * Assures write permission for given file.
	 *
	 * @param FileInterface $file
	 * @return void
	 * @throws Exception\IllegalFileExtensionException
	 * @throws Exception\InsufficientFileWritePermissionsException
	 * @throws Exception\InsufficientUserPermissionsException
	 */
	protected function assureFileWritePermissions(FileInterface $file) {
		// Check if user is allowed to write the file and $file is writable
		if (!$this->checkFileActionPermission('write', $file)) {
			throw new Exception\InsufficientFileWritePermissionsException('Writing to file "' . $file->getIdentifier() . '" is not allowed.', 1330121088);
		}
		if (!$this->checkFileExtensionPermission($file->getName())) {
			throw new Exception\IllegalFileExtensionException('You are not allowed to edit a file with extension "' . $file->getExtension() . '"', 1366711933);
		}
	}

	/**
	 * Assures delete permission for given file.
	 *
	 * @param FileInterface $file
	 * @return void
	 * @throws Exception\IllegalFileExtensionException
	 * @throws Exception\InsufficientFileWritePermissionsException
	 * @throws Exception\InsufficientFolderWritePermissionsException
	 */
	protected function assureFileDeletePermissions(FileInterface $file) {
		// Check for disallowed file extensions
		if (!$this->checkFileExtensionPermission($file->getName())) {
			throw new Exception\IllegalFileExtensionException('You are not allowed to delete a file with extension "' . $file->getExtension() . '"', 1377778916);
		}
		// Check further permissions if file is not a processed file
		if (!$file instanceof ProcessedFile) {
			// Check if user is allowed to delete the file and $file is writable
			if (!$this->checkFileActionPermission('delete', $file)) {
				throw new Exception\InsufficientFileWritePermissionsException('You are not allowed to delete the file "' . $file->getIdentifier() . '"', 1319550425);
			}
			// Check if the user has write permissions to folders
			// Would be good if we could check for actual write permissions in the containig folder
			// but we cannot since we have no access to the containing folder of this file.
			if (!$this->checkUserActionPermission('write', 'Folder')) {
				throw new Exception\InsufficientFolderWritePermissionsException('Writing to folders is not allowed.', 1377778702);
			}
		}
	}

	/**
	 * Checks if a file has the permission to be uploaded to a Folder/Storage.
	 * If not, throws an exception.
	 *
	 * @param string $localFilePath the temporary file name from $_FILES['file1']['tmp_name']
	 * @param Folder $targetFolder
	 * @param string $targetFileName the destination file name $_FILES['file1']['name']
	 * @return void
	 *
	 * @throws Exception\InsufficientFolderWritePermissionsException
	 * @throws Exception\UploadException
	 * @throws Exception\IllegalFileExtensionException
	 * @throws Exception\UploadSizeException
	 * @throws Exception\InsufficientUserPermissionsException
	 */
	protected function assureFileAddPermissions($localFilePath, $targetFolder, $targetFileName) {
		// Check for a valid file extension
		if (!$this->checkFileExtensionPermission($targetFileName) || ($localFilePath && !$this->checkFileExtensionPermission($localFilePath))) {
			throw new Exception\IllegalFileExtensionException('Extension of file name is not allowed in "' . $targetFileName . '"!', 1322120271);
		}
		// Makes sure the user is allowed to upload
		if (!$this->checkUserActionPermission('add', 'File')) {
			throw new Exception\InsufficientUserPermissionsException('You are not allowed to add files to this storage "' . $this->getUid() . '"', 1376992145);
		}
		// Check if targetFolder is writable
		if (!$this->checkFolderActionPermission('write', $targetFolder)) {
			throw new Exception\InsufficientFolderWritePermissionsException('You are not allowed to write to the target folder "' . $targetFolder->getIdentifier() . '"', 1322120356);
		}
	}

	/**
	 * Checks if a file has the permission to be uploaded to a Folder/Storage.
	 * If not, throws an exception.
	 *
	 * @param string $localFilePath the temporary file name from $_FILES['file1']['tmp_name']
	 * @param Folder $targetFolder
	 * @param string $targetFileName the destination file name $_FILES['file1']['name']
	 * @param int $uploadedFileSize
	 * @return void
	 *
	 * @throws Exception\InsufficientFolderWritePermissionsException
	 * @throws Exception\UploadException
	 * @throws Exception\IllegalFileExtensionException
	 * @throws Exception\UploadSizeException
	 * @throws Exception\InsufficientUserPermissionsException
	 */
	protected function assureFileUploadPermissions($localFilePath, $targetFolder, $targetFileName, $uploadedFileSize) {
		// Makes sure this is an uploaded file
		if (!is_uploaded_file($localFilePath)) {
			throw new Exception\UploadException('The upload has failed, no uploaded file found!', 1322110455);
		}
		// Max upload size (kb) for files.
		$maxUploadFileSize = GeneralUtility::getMaxUploadFileSize() * 1024;
		if ($uploadedFileSize >= $maxUploadFileSize) {
			unlink($localFilePath);
			throw new Exception\UploadSizeException('The uploaded file exceeds the size-limit of ' . $maxUploadFileSize . ' bytes', 1322110041);
		}
		$this->assureFileAddPermissions($localFilePath, $targetFolder, $targetFileName);
	}

	/**
	 * Checks for permissions to move a file.
	 *
	 * @throws \RuntimeException
	 * @throws Exception\InsufficientFolderAccessPermissionsException
	 * @throws Exception\InsufficientUserPermissionsException
	 * @throws Exception\IllegalFileExtensionException
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @param string $targetFileName
	 * @return void
	 */
	protected function assureFileMovePermissions(FileInterface $file, Folder $targetFolder, $targetFileName) {
		// Check if targetFolder is within this storage
		if ($this->getUid() !== $targetFolder->getStorage()->getUid()) {
			throw new \RuntimeException();
		}
		// Check for a valid file extension
		if (!$this->checkFileExtensionPermission($targetFileName)) {
			throw new Exception\IllegalFileExtensionException('Extension of file name is not allowed in "' . $targetFileName . '"!', 1378243279);
		}
		// Check if user is allowed to move and $file is readable and writable
		if (!$file->getStorage()->checkFileActionPermission('move', $file)) {
			throw new Exception\InsufficientUserPermissionsException('You are not allowed to move files to storage "' . $this->getUid() . '"', 1319219349);
		}
		// Check if target folder is writable
		if (!$this->checkFolderActionPermission('write', $targetFolder)) {
			throw new Exception\InsufficientFolderAccessPermissionsException('You are not allowed to write to the target folder "' . $targetFolder->getIdentifier() . '"', 1319219350);
		}
	}

	/**
	 * Checks for permissions to rename a file.
	 *
	 * @param FileInterface $file
	 * @param string $targetFileName
	 * @throws Exception\InsufficientFileWritePermissionsException
	 * @throws Exception\IllegalFileExtensionException
	 * @throws Exception\InsufficientFileReadPermissionsException
	 * @throws Exception\InsufficientUserPermissionsException
	 * @return void
	 */
	protected function assureFileRenamePermissions(FileInterface $file, $targetFileName) {
		// Check if file extension is allowed
		if (!$this->checkFileExtensionPermission($targetFileName) || !$this->checkFileExtensionPermission($file->getName())) {
			throw new Exception\IllegalFileExtensionException('You are not allowed to rename a file with to this extension', 1371466663);
		}
		// Check if user is allowed to rename
		if (!$this->checkFileActionPermission('rename', $file)) {
			throw new Exception\InsufficientUserPermissionsException('You are not allowed to rename files."', 1319219351);
		}
		// Check if the user is allowed to write to folders
		// Although it would be good to check, we cannot check here if the folder actually is writable
		// because we do not know in which folder the file resides.
		// So we rely on the driver to throw an exception in case the renaming failed.
		if (!$this->checkFolderActionPermission('write')) {
			throw new Exception\InsufficientFileWritePermissionsException('You are not allowed to write to folders', 1319219352);
		}
	}

	/**
	 * Check if a file has the permission to be copied on a File/Folder/Storage,
	 * if not throw an exception
	 *
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @param string $targetFileName
	 *
	 * @throws Exception
	 * @throws Exception\InsufficientFolderWritePermissionsException
	 * @throws Exception\IllegalFileExtensionException
	 * @throws Exception\InsufficientFileReadPermissionsException
	 * @throws Exception\InsufficientUserPermissionsException
	 * @return void
	 */
	protected function assureFileCopyPermissions(FileInterface $file, Folder $targetFolder, $targetFileName) {
		// Check if targetFolder is within this storage, this should never happen
		if ($this->getUid() != $targetFolder->getStorage()->getUid()) {
			throw new Exception('The operation of the folder cannot be called by this storage "' . $this->getUid() . '"', 1319550405);
		}
		// Check if user is allowed to copy
		if (!$file->getStorage()->checkFileActionPermission('copy', $file)) {
			throw new Exception\InsufficientFileReadPermissionsException('You are not allowed to copy the file "' . $file->getIdentifier() . '"', 1319550426);
		}
		// Check if targetFolder is writable
		if (!$this->checkFolderActionPermission('write', $targetFolder)) {
			throw new Exception\InsufficientFolderWritePermissionsException('You are not allowed to write to the target folder "' . $targetFolder->getIdentifier() . '"', 1319550435);
		}
		// Check for a valid file extension
		if (!$this->checkFileExtensionPermission($targetFileName) || !$this->checkFileExtensionPermission($file->getName())) {
			throw new Exception\IllegalFileExtensionException('You are not allowed to copy a file of that type.', 1319553317);
		}
	}

	/**
	 * Check if a file has the permission to be copied on a File/Folder/Storage,
	 * if not throw an exception
	 *
	 * @param FolderInterface $folderToCopy
	 * @param FolderInterface $targetParentFolder
	 * @return void
	 *
	 * @throws Exception
	 * @throws Exception\InsufficientFolderWritePermissionsException
	 * @throws Exception\IllegalFileExtensionException
	 * @throws Exception\InsufficientFileReadPermissionsException
	 * @throws Exception\InsufficientUserPermissionsException
	 * @throws \RuntimeException
	 */
	protected function assureFolderCopyPermissions(FolderInterface $folderToCopy, FolderInterface $targetParentFolder) {
		// Check if targetFolder is within this storage, this should never happen
		if ($this->getUid() !== $targetParentFolder->getStorage()->getUid()) {
			throw new Exception('The operation of the folder cannot be called by this storage "' . $this->getUid() . '"', 1377777624);
		}
		if (!$folderToCopy instanceof Folder) {
			throw new \RuntimeException('The folder "' . $folderToCopy->getIdentifier() . '" to copy is not of type Folder.', 1384209020);
		}
		// Check if user is allowed to copy and the folder is readable
		if (!$folderToCopy->getStorage()->checkFolderActionPermission('copy', $folderToCopy)) {
			throw new Exception\InsufficientFileReadPermissionsException('You are not allowed to copy the folder "' . $folderToCopy->getIdentifier() . '"', 1377777629);
		}
		if (!$targetParentFolder instanceof Folder) {
			throw new \RuntimeException('The target folder "' . $targetParentFolder->getIdentifier() . '" is not of type Folder.', 1384209021);
		}
		// Check if targetFolder is writable
		if (!$this->checkFolderActionPermission('write', $targetParentFolder)) {
			throw new Exception\InsufficientFolderWritePermissionsException('You are not allowed to write to the target folder "' . $targetParentFolder->getIdentifier() . '"', 1377777635);
		}
	}

	/**
	 * Check if a file has the permission to be copied on a File/Folder/Storage,
	 * if not throw an exception
	 *
	 * @param FolderInterface $folderToMove
	 * @param FolderInterface $targetParentFolder
	 *
	 * @throws \InvalidArgumentException
	 * @throws Exception\InsufficientFolderWritePermissionsException
	 * @throws Exception\IllegalFileExtensionException
	 * @throws Exception\InsufficientFileReadPermissionsException
	 * @throws Exception\InsufficientUserPermissionsException
	 * @throws \RuntimeException
	 * @return void
	 */
	protected function assureFolderMovePermissions(FolderInterface $folderToMove, FolderInterface $targetParentFolder) {
		// Check if targetFolder is within this storage, this should never happen
		if ($this->getUid() !== $targetParentFolder->getStorage()->getUid()) {
			throw new \InvalidArgumentException('Cannot move a folder into a folder that does not belong to this storage.', 1325777289);
		}
		if (!$folderToMove instanceof Folder) {
			throw new \RuntimeException('The folder "' . $folderToMove->getIdentifier() . '" to move is not of type Folder.', 1384209022);
		}
		// Check if user is allowed to move and the folder is writable
		// In fact we would need to check if the parent folder of the folder to move is writable also
		// But as of now we cannot extract the parent folder from this folder
		if (!$folderToMove->getStorage()->checkFolderActionPermission('move', $folderToMove)) {
			throw new Exception\InsufficientFileReadPermissionsException('You are not allowed to copy the folder "' . $folderToMove->getIdentifier() . '"', 1377778045);
		}
		if (!$targetParentFolder instanceof Folder) {
			throw new \RuntimeException('The target folder "' . $targetParentFolder->getIdentifier() . '" is not of type Folder.', 1384209023);
		}
		// Check if targetFolder is writable
		if (!$this->checkFolderActionPermission('write', $targetParentFolder)) {
			throw new Exception\InsufficientFolderWritePermissionsException('You are not allowed to write to the target folder "' . $targetParentFolder->getIdentifier() . '"', 1377778049);
		}
	}

	/********************
	 * FILE ACTIONS
	 ********************/
	/**
	 * Moves a file from the local filesystem to this storage.
	 *
	 * @param string $localFilePath The file on the server's hard disk to add.
	 * @param Folder $targetFolder The target path, without the fileName
	 * @param string $targetFileName The fileName. If not set, the local file name is used.
	 * @param string $conflictMode possible value are 'cancel', 'replace', 'changeName'
	 *
	 * @throws \InvalidArgumentException
	 * @throws Exception\ExistingTargetFileNameException
	 * @return FileInterface
	 */
	public function addFile($localFilePath, Folder $targetFolder, $targetFileName = '', $conflictMode = 'changeName') {
		$localFilePath = PathUtility::getCanonicalPath($localFilePath);
		if (!file_exists($localFilePath)) {
			throw new \InvalidArgumentException('File "' . $localFilePath . '" does not exist.', 1319552745);
		}
		$this->assureFileAddPermissions($localFilePath, $targetFolder, $targetFileName);
		$targetFolder = $targetFolder ?: $this->getDefaultFolder();
		$targetFileName = $this->driver->sanitizeFileName($targetFileName ?: PathUtility::basename($localFilePath));

		// We do not care whether the file exists yet because $targetFileName may be changed by an
		// external slot and only then we should check how to proceed according to $conflictMode
		$this->emitPreFileAddSignal($targetFileName, $targetFolder, $localFilePath);

		if ($conflictMode === 'cancel' && $this->driver->fileExistsInFolder($targetFileName, $targetFolder->getIdentifier())) {
			throw new Exception\ExistingTargetFileNameException('File "' . $targetFileName . '" already exists in folder ' . $targetFolder->getIdentifier(), 1322121068);
		} elseif ($conflictMode === 'changeName') {
			$targetFileName = $this->getUniqueName($targetFolder, $targetFileName);
		}

		$fileIdentifier = $this->driver->addFile($localFilePath, $targetFolder->getIdentifier(), $targetFileName);
		$file = ResourceFactory::getInstance()->getFileObjectByStorageAndIdentifier($this->getUid(), $fileIdentifier);

		$this->emitPostFileAddSignal($file, $targetFolder);

		return $file;
	}

	/**
	 * Updates a processed file with a new file from the local filesystem.
	 *
	 * @param $localFilePath
	 * @param ProcessedFile $processedFile
	 * @return FileInterface
	 * @throws \InvalidArgumentException
	 * @internal use only
	 */
	public function updateProcessedFile($localFilePath, ProcessedFile $processedFile) {
		if (!file_exists($localFilePath)) {
			throw new \InvalidArgumentException('File "' . $localFilePath . '" does not exist.', 1319552746);
		}
		$fileIdentifier = $this->driver->addFile($localFilePath, $this->getProcessingFolder()->getIdentifier(), $processedFile->getName());
		// @todo check if we have to update the processed file other then the identifier
		$processedFile->setIdentifier($fileIdentifier);
		return $processedFile;
	}

	/**
	 * Creates a (cryptographic) hash for a file.
	 *
	 * @param FileInterface $fileObject
	 * @param string $hash
	 * @return string
	 */
	public function hashFile(FileInterface $fileObject, $hash) {
		return $this->hashFileByIdentifier($fileObject->getIdentifier(), $hash);
	}

	/**
	 * Creates a (cryptographic) hash for a fileIdentifier.

	 * @param string $fileIdentifier
	 * @param string $hash
	 *
	 * @return string
	 */
	public function hashFileByIdentifier($fileIdentifier, $hash) {
		return $this->driver->hash($fileIdentifier, $hash);
	}

	/**
	 * Hashes a file identifier, taking the case sensitivity of the file system
	 * into account. This helps mitigating problems with case-insensitive
	 * databases.
	 *
	 * @param string|FileInterface $file
	 * @return string
	 */
	public function hashFileIdentifier($file) {
		if (is_object($file) && $file instanceof FileInterface) {
			/** @var FileInterface $file */
			$file = $file->getIdentifier();
		}
		return $this->driver->hashIdentifier($file);
	}

	/**
	 * Returns a publicly accessible URL for a file.
	 *
	 * WARNING: Access to the file may be restricted by further means, e.g.
	 * some web-based authentication. You have to take care of this yourself.
	 *
	 * @param ResourceInterface $resourceObject The file or folder object
	 * @param boolean $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 * @return string
	 */
	public function getPublicUrl(ResourceInterface $resourceObject, $relativeToCurrentScript = FALSE) {
		$publicUrl = NULL;
		if ($this->isOnline()) {
			// Pre-process the public URL by an accordant slot
			$this->emitPreGeneratePublicUrl($resourceObject, $relativeToCurrentScript, array('publicUrl' => &$publicUrl));
			// If slot did not handle the signal, use the default way to determine public URL
			if ($publicUrl === NULL) {

				if ($this->hasCapability(self::CAPABILITY_PUBLIC)) {
					$publicUrl = $this->driver->getPublicUrl($resourceObject->getIdentifier());
				}

				if ($publicUrl === NULL && $resourceObject instanceof FileInterface) {
					$queryParameterArray = array('eID' => 'dumpFile', 't' => '');
					if ($resourceObject instanceof File) {
						$queryParameterArray['f'] = $resourceObject->getUid();
						$queryParameterArray['t'] = 'f';
					} elseif ($resourceObject instanceof ProcessedFile) {
						$queryParameterArray['p'] = $resourceObject->getUid();
						$queryParameterArray['t'] = 'p';
					}

					$queryParameterArray['token'] = GeneralUtility::hmac(implode('|', $queryParameterArray), 'resourceStorageDumpFile');
					$publicUrl = 'index.php?' . str_replace('+', '%20', http_build_query($queryParameterArray));
				}

				// If requested, make the path relative to the current script in order to make it possible
				// to use the relative file
				if ($publicUrl !== NULL && $relativeToCurrentScript && !GeneralUtility::isValidUrl($publicUrl)) {
					$absolutePathToContainingFolder = PathUtility::dirname(PATH_site . $publicUrl);
					$pathPart = PathUtility::getRelativePathTo($absolutePathToContainingFolder);
					$filePart = substr(PATH_site . $publicUrl, strlen($absolutePathToContainingFolder) + 1);
					$publicUrl = $pathPart . $filePart;
				}
			}
		}
		return $publicUrl;
	}

	/**
	 * Passes a file to the File Processing Services and returns the resulting ProcessedFile object.
	 *
	 * @param FileInterface $fileObject The file object
	 * @param string $context
	 * @param array $configuration
	 *
	 * @return ProcessedFile
	 * @throws \InvalidArgumentException
	 */
	public function processFile(FileInterface $fileObject, $context, array $configuration) {
		if ($fileObject->getStorage() !== $this) {
			throw new \InvalidArgumentException('Cannot process files of foreign storage', 1353401835);
		}
		$processedFile = $this->getFileProcessingService()->processFile($fileObject, $this, $context, $configuration);

		return $processedFile;
	}

	/**
	 * Copies a file from the storage for local processing.
	 *
	 * @param FileInterface $fileObject
	 * @param boolean $writable
	 * @return string Path to local file (either original or copied to some temporary local location)
	 */
	public function getFileForLocalProcessing(FileInterface $fileObject, $writable = TRUE) {
		$filePath = $this->driver->getFileForLocalProcessing($fileObject->getIdentifier(), $writable);
		return $filePath;
	}

	/**
	 * Gets a file by identifier.
	 *
	 * @param string $identifier
	 * @return FileInterface
	 */
	public function getFile($identifier) {
		$file =  $this->getFileFactory()->getFileObjectByStorageAndIdentifier($this->getUid(), $identifier);
		if (!$this->driver->fileExists($identifier)) {
			$file->setMissing(TRUE);
		}
		return $file;
	}

	/**
	 * Gets information about a file.
	 *
	 * @param FileInterface $fileObject
	 * @return array
	 * @internal
	 */
	public function getFileInfo(FileInterface $fileObject) {
		return $this->getFileInfoByIdentifier($fileObject->getIdentifier());
	}

	/**
	 * Gets information about a file by its identifier.
	 *
	 * @param string $identifier
	 * @param array $propertiesToExtract
	 * @return array
	 * @internal
	 */
	public function getFileInfoByIdentifier($identifier, array $propertiesToExtract = array()) {
		return $this->driver->getFileInfoByIdentifier($identifier, $propertiesToExtract);
	}

	/**
	 * Unsets the file and folder name filters, thus making this storage return unfiltered file lists.
	 *
	 * @return void
	 */
	public function unsetFileAndFolderNameFilters() {
		$this->fileAndFolderNameFilters = array();
	}

	/**
	 * Resets the file and folder name filters to the default values defined in the TYPO3 configuration.
	 *
	 * @return void
	 */
	public function resetFileAndFolderNameFiltersToDefault() {
		$this->fileAndFolderNameFilters = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['defaultFilterCallbacks'];
	}

	/**
	 * Returns the file and folder name filters used by this storage.
	 *
	 * @return array
	 */
	public function getFileAndFolderNameFilters() {
		return $this->fileAndFolderNameFilters;
	}

	/**
	 * @param array $filters
	 * @return $this
	 */
	public function setFileAndFolderNameFilters(array $filters) {
		$this->fileAndFolderNameFilters = $filters;
		return $this;
	}

	/**
	 * @param array $filter
	 */
	public function addFileAndFolderNameFilter($filter) {
		$this->fileAndFolderNameFilters[] = $filter;
	}

	/**
	 * @param string $fileIdentifier
	 *
	 * @return string
	 */
	public function getFolderIdentifierFromFileIdentifier($fileIdentifier) {
		return $this->driver->getParentFolderIdentifierOfIdentifier($fileIdentifier);
	}

	/**
	 * Returns a list of files in a given path, filtered by some custom filter methods.
	 *
	 * @see getUnfilteredFileList(), getFileListWithDefaultFilters()
	 * @param string $path The path to list
	 * @param int $start The position to start the listing; if not set or 0, start from the beginning
	 * @param int $numberOfItems The number of items to list; if not set, return all items
	 * @param bool $useFilters If FALSE, the list is returned without any filtering; otherwise, the filters defined for this storage are used.
	 * @param bool $loadIndexRecords If set to TRUE, the index records for all files are loaded from the database. This can greatly improve performance of this method, especially with a lot of files.
	 * @param bool $recursive
	 * @return array Information about the files found.
	 * @deprecated since 6.2, will be removed two versions later
	 */
	public function getFileList($path, $start = 0, $numberOfItems = 0, $useFilters = TRUE, $loadIndexRecords = TRUE, $recursive = FALSE) {
		GeneralUtility::logDeprecatedFunction();
		return $this->getFilesInFolder($this->getFolder($path), $start, $numberOfItems, $useFilters, $recursive);
	}

	/**
	 * @param Folder $folder
	 * @param int $start
	 * @param int $maxNumberOfItems
	 * @param bool $useFilters
	 * @param bool $recursive
	 * @return File[]
	 */
	public function getFilesInFolder(Folder $folder, $start = 0, $maxNumberOfItems = 0, $useFilters = TRUE, $recursive = FALSE) {
		$this->assureFolderReadPermission($folder);

		$rows = $this->getFileIndexRepository()->findByFolder($folder);

		$filters = $useFilters == TRUE ? $this->fileAndFolderNameFilters : array();
		$fileIdentifiers = array_values($this->driver->getFilesInFolder($folder->getIdentifier(), $start, $maxNumberOfItems, $recursive, $filters));
		$fileIdentifiersCount = count($fileIdentifiers);
		$items = array();
		if ($maxNumberOfItems === 0) {
			$maxNumberOfItems = $fileIdentifiersCount;
		}
		$end = min($fileIdentifiersCount, $start + $maxNumberOfItems);
		for ($i = $start; $i < $end; $i++) {
			$identifier = $fileIdentifiers[$i];
			if (isset($rows[$identifier])) {
				$fileObject = $this->getFileFactory()->getFileObject($rows[$identifier]['uid'], $rows[$identifier]);
			} else {
				$fileObject = $this->getFileFactory()->getFileObjectByStorageAndIdentifier($this->getUid(), $identifier);
			}
			$key = $fileObject->getName();
			while (isset($items[$key])) {
				$key .= 'z';
			}
			$items[$key] = $fileObject;
		}
		uksort($items, 'strnatcasecmp');

		return $items;
	}

	/**
	 * @param string $folderIdentifier
	 * @param bool $useFilters
	 * @param bool $recursive
	 *
	 * @return array
	 */
	public function getFileIdentifiersInFolder($folderIdentifier, $useFilters = TRUE, $recursive = FALSE) {
		$filters = $useFilters == TRUE ? $this->fileAndFolderNameFilters : array();
		return $this->driver->getFilesInFolder($folderIdentifier, 0, 0, $recursive, $filters);
	}

	/**
	 * @param string $folderIdentifier
	 * @param bool $useFilters
	 * @param bool $recursive
	 *
	 * @return array
	 */
	public function getFolderIdentifiersInFolder($folderIdentifier, $useFilters = TRUE, $recursive = FALSE) {
		$filters = $useFilters == TRUE ? $this->fileAndFolderNameFilters : array();
		return $this->driver->getFoldersInFolder($folderIdentifier, 0, 0, $recursive, $filters);
	}


	/**
	 * Returns TRUE if the specified file exists.
	 *
	 * @param string $identifier
	 * @return bool
	 */
	public function hasFile($identifier) {
		// Allow if identifier is in processing folder
		if (!$this->driver->isWithin($this->getProcessingFolder()->getIdentifier(), $identifier)) {
			$this->assureFolderReadPermission();
		}
		return $this->driver->fileExists($identifier);
	}

	/**
	 * Checks if the queried file in the given folder exists.
	 *
	 * @param string $fileName
	 * @param Folder $folder
	 * @return bool
	 */
	public function hasFileInFolder($fileName, Folder $folder) {
		$this->assureFolderReadPermission($folder);
		return $this->driver->fileExistsInFolder($fileName, $folder->getIdentifier());
	}

	/**
	 * Get contents of a file object
	 *
	 * @param FileInterface $file
	 *
	 * @throws Exception\InsufficientFileReadPermissionsException
	 * @return string
	 */
	public function getFileContents($file) {
		$this->assureFileReadPermission($file);
		return $this->driver->getFileContents($file->getIdentifier());
	}

	/**
	 * Outputs file Contents,
	 * clears output buffer first and sends headers accordingly.
	 *
	 * @param FileInterface $file
	 * @param bool $asDownload If set Content-Disposition attachment is sent, inline otherwise
	 * @param string $alternativeFilename the filename for the download (if $asDownload is set)
	 * @return void
	 */
	public function dumpFileContents(FileInterface $file, $asDownload = FALSE, $alternativeFilename = NULL) {
		$downloadName = $alternativeFilename ?: $file->getName();
		$contentDisposition = $asDownload ? 'attachment' : 'inline';
		header('Content-Disposition: ' . $contentDisposition . '; filename="' . $downloadName . '"');
		header('Content-Type: ' . $file->getMimeType());
		header('Content-Length: ' . $file->getSize());

		// Cache-Control header is needed here to solve an issue with browser IE8 and lower
		// See for more information: http://support.microsoft.com/kb/323308
		header("Cache-Control: ''");
		header('Last-Modified: ' .
			gmdate('D, d M Y H:i:s', array_pop($this->driver->getFileInfoByIdentifier($file->getIdentifier(), array('mtime')))) . ' GMT',
			TRUE,
			200
		);
		ob_clean();
		flush();
		$this->driver->dumpFileContents($file->getIdentifier());
	}

	/**
	 * Set contents of a file object.
	 *
	 * @param AbstractFile $file
	 * @param string $contents
	 *
	 * @throws \Exception|\RuntimeException
	 * @throws Exception\InsufficientFileWritePermissionsException
	 * @throws Exception\InsufficientUserPermissionsException
	 * @return int The number of bytes written to the file
	 */
	public function setFileContents(AbstractFile $file, $contents) {
		// Check if user is allowed to edit
		$this->assureFileWritePermissions($file);
		// Call driver method to update the file and update file index entry afterwards
		$result = $this->driver->setFileContents($file->getIdentifier(), $contents);
		$this->getIndexer()->updateIndexEntry($file);
		return $result;
	}

	/**
	 * Creates a new file
	 *
	 * previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::func_newfile()
	 *
	 * @param string $fileName
	 * @param Folder $targetFolderObject
	 *
	 * @throws Exception\IllegalFileExtensionException
	 * @throws Exception\InsufficientFolderWritePermissionsException
	 * @return FileInterface The file object
	 */
	public function createFile($fileName, Folder $targetFolderObject) {
		$this->assureFileAddPermissions('', $targetFolderObject, $fileName);
		$newFileIdentifier = $this->driver->createFile($fileName, $targetFolderObject->getIdentifier());
		return ResourceFactory::getInstance()->getFileObjectByStorageAndIdentifier($this->getUid(), $newFileIdentifier);
	}

	/**
	 * Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::deleteFile()
	 *
	 * @param $fileObject FileInterface
	 * @throws Exception\InsufficientFileAccessPermissionsException
	 * @throws Exception\FileOperationErrorException
	 * @return bool TRUE if deletion succeeded
	 */
	public function deleteFile($fileObject) {
		$this->assureFileDeletePermissions($fileObject);

		$this->emitPreFileDeleteSignal($fileObject);

		$result = $this->driver->deleteFile($fileObject->getIdentifier());
		if ($result === FALSE) {
			throw new Exception\FileOperationErrorException('Deleting the file "' . $fileObject->getIdentifier() . '\' failed.', 1329831691);
		}
		// Mark the file object as deleted
		if ($fileObject instanceof File) {
			$fileObject->setDeleted();
		}

		$this->emitPostFileDeleteSignal($fileObject);

		return TRUE;
	}

	/**
	 * Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::func_copy()
	 * copies a source file (from any location) in to the target
	 * folder, the latter has to be part of this storage
	 *
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @param string $targetFileName an optional destination fileName
	 * @param string $conflictMode "overrideExistingFile", "renameNewFile", "cancel
	 *
	 * @throws \Exception|Exception\AbstractFileOperationException
	 * @throws Exception\ExistingTargetFileNameException
	 * @return FileInterface
	 */
	public function copyFile(FileInterface $file, Folder $targetFolder, $targetFileName = NULL, $conflictMode = 'renameNewFile') {
		if ($targetFileName === NULL) {
			$targetFileName = $file->getName();
		}
		$sanitizedTargetFileName = $this->driver->sanitizeFileName($targetFileName);
		$this->assureFileCopyPermissions($file, $targetFolder, $sanitizedTargetFileName);
		$this->emitPreFileCopySignal($file, $targetFolder);
		// File exists and we should abort, let's abort
		if ($conflictMode === 'cancel' && $targetFolder->hasFile($sanitizedTargetFileName)) {
			throw new Exception\ExistingTargetFileNameException('The target file already exists.', 1320291064);
		}
		// File exists and we should find another name, let's find another one
		if ($conflictMode === 'renameNewFile' && $targetFolder->hasFile($sanitizedTargetFileName)) {
			$sanitizedTargetFileName = $this->getUniqueName($targetFolder, $sanitizedTargetFileName);
		}
		$sourceStorage = $file->getStorage();
		// Call driver method to create a new file from an existing file object,
		// and return the new file object
		if ($sourceStorage === $this) {
			$newFileObjectIdentifier = $this->driver->copyFileWithinStorage($file->getIdentifier(), $targetFolder->getIdentifier(), $sanitizedTargetFileName);
		} else {
			$tempPath = $file->getForLocalProcessing();
			$newFileObjectIdentifier = $this->driver->addFile($tempPath, $targetFolder->getIdentifier(), $sanitizedTargetFileName);
		}
		$newFileObject = ResourceFactory::getInstance()->getFileObjectByStorageAndIdentifier($this->getUid(), $newFileObjectIdentifier);
		$this->emitPostFileCopySignal($file, $targetFolder);
		return $newFileObject;
	}

	/**
	 * Moves a $file into a $targetFolder
	 * the target folder has to be part of this storage
	 *
	 * previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::func_move()
	 *
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @param string $targetFileName an optional destination fileName
	 * @param string $conflictMode "overrideExistingFile", "renameNewFile", "cancel
	 *
	 * @throws Exception\ExistingTargetFileNameException
	 * @throws \RuntimeException
	 * @return FileInterface
	 */
	public function moveFile($file, $targetFolder, $targetFileName = NULL, $conflictMode = 'renameNewFile') {
		if ($targetFileName === NULL) {
			$targetFileName = $file->getName();
		}
		$sanitizedTargetFileName = $this->driver->sanitizeFileName($targetFileName);
		$this->assureFileMovePermissions($file, $targetFolder, $sanitizedTargetFileName);
		if ($targetFolder->hasFile($sanitizedTargetFileName)) {
			// File exists and we should abort, let's abort
			if ($conflictMode === 'renameNewFile') {
				$sanitizedTargetFileName = $this->getUniqueName($targetFolder, $sanitizedTargetFileName);
			} elseif ($conflictMode === 'cancel') {
				throw new Exception\ExistingTargetFileNameException('The target file already exists', 1329850997);
			}
		}
		$this->emitPreFileMoveSignal($file, $targetFolder);
		$sourceStorage = $file->getStorage();
		// Call driver method to move the file and update the index entry
		try {
			if ($sourceStorage === $this) {
				$newIdentifier = $this->driver->moveFileWithinStorage($file->getIdentifier(), $targetFolder->getIdentifier(), $sanitizedTargetFileName);
				if (!$file instanceof AbstractFile) {
					throw new \RuntimeException('The given file is not of type AbstractFile.', 1384209025);
				}
				$file->updateProperties(array('identifier' => $newIdentifier));
			} else {
				$tempPath = $file->getForLocalProcessing();
				$newIdentifier = $this->driver->addFile($tempPath, $targetFolder->getIdentifier(), $sanitizedTargetFileName);
				$sourceStorage->driver->deleteFile($file->getIdentifier());
				if ($file instanceof File) {
					$file->updateProperties(array('storage' => $this->getUid(), 'identifier' => $newIdentifier));
				}
			}
			$this->getIndexer()->updateIndexEntry($file);
		} catch (\TYPO3\CMS\Core\Exception $e) {
			echo $e->getMessage();
		}
		$this->emitPostFileMoveSignal($file, $targetFolder);
		return $file;
	}

	/**
	 * Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::func_rename()
	 *
	 * @param FileInterface $file
	 * @param string $targetFileName
	 *
	 * @throws Exception\InsufficientFileWritePermissionsException
	 * @throws Exception\InsufficientFileReadPermissionsException
	 * @throws Exception\InsufficientUserPermissionsException
	 * @return FileInterface
	 */
	public function renameFile($file, $targetFileName) {
		// TODO add $conflictMode setting

		// The name should be different from the current.
		if ($file->getName() === $targetFileName) {
			return $file;
		}
		$sanitizedTargetFileName = $this->driver->sanitizeFileName($targetFileName);
		$this->assureFileRenamePermissions($file, $sanitizedTargetFileName);
		$this->emitPreFileRenameSignal($file, $sanitizedTargetFileName);

		// Call driver method to rename the file and update the index entry
		try {
			$newIdentifier = $this->driver->renameFile($file->getIdentifier(), $sanitizedTargetFileName);
			if ($file instanceof File) {
				$file->updateProperties(array('identifier' => $newIdentifier));
			}
			$this->getIndexer()->updateIndexEntry($file);
		} catch (\RuntimeException $e) {

		}

		$this->emitPostFileRenameSignal($file, $sanitizedTargetFileName);

		return $file;
	}

	/**
	 * Replaces a file with a local file (e.g. a freshly uploaded file)
	 *
	 * @param FileInterface $file
	 * @param string $localFilePath
	 *
	 * @return FileInterface
	 *
	 * @throws Exception\IllegalFileExtensionException
	 * @throws \InvalidArgumentException
	 */
	public function replaceFile(FileInterface $file, $localFilePath) {
		$this->assureFileWritePermissions($file);
		if (!$this->checkFileExtensionPermission($localFilePath)) {
			throw new Exception\IllegalFileExtensionException('Source file extension not allowed.', 1378132239);
		}
		if (!file_exists($localFilePath)) {
			throw new \InvalidArgumentException('File "' . $localFilePath . '" does not exist.', 1325842622);
		}
		$this->emitPreFileReplaceSignal($file, $localFilePath);
		$result = $this->driver->replaceFile($file->getIdentifier(), $localFilePath);
		if ($file instanceof File) {
			$this->getIndexer()->updateIndexEntry($file);
		}
		$this->emitPostFileReplaceSignal($file, $localFilePath);
		return $result;
	}

	/**
	 * Adds an uploaded file into the Storage. Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::file_upload()
	 *
	 * @param array $uploadedFileData contains information about the uploaded file given by $_FILES['file1']
	 * @param Folder $targetFolder the target folder
	 * @param string $targetFileName the file name to be written
	 * @param string $conflictMode possible value are 'cancel', 'replace'
	 * @return FileInterface The file object
	 */
	public function addUploadedFile(array $uploadedFileData, Folder $targetFolder = NULL, $targetFileName = NULL, $conflictMode = 'cancel') {
		$localFilePath = $uploadedFileData['tmp_name'];
		if ($targetFolder === NULL) {
			$targetFolder = $this->getDefaultFolder();
		}
		if ($targetFileName === NULL) {
			$targetFileName = $uploadedFileData['name'];
		}
		// Handling $conflictMode is delegated to addFile()
		$this->assureFileUploadPermissions($localFilePath, $targetFolder, $targetFileName, $uploadedFileData['size']);
		$resultObject = $this->addFile($localFilePath, $targetFolder, $targetFileName, $conflictMode);
		return $resultObject;
	}

	/********************
	 * FOLDER ACTIONS
	 ********************/
	/**
	 * Returns an array with all file objects in a folder and its subfolders, with the file identifiers as keys.
	 * @todo check if this is a duplicate
	 * @param Folder $folder
	 * @return File[]
	 */
	protected function getAllFileObjectsInFolder(Folder $folder) {
		$files = array();
		$folderQueue = array($folder);
		while (!empty($folderQueue)) {
			$folder = array_shift($folderQueue);
			foreach ($folder->getSubfolders() as $subfolder) {
				$folderQueue[] = $subfolder;
			}
			foreach ($folder->getFiles() as $file) { /** @var FileInterface $file */
				$files[$file->getIdentifier()] = $file;
			}
		}
		return $files;
	}

	/**
	 * Moves a folder. If you want to move a folder from this storage to another
	 * one, call this method on the target storage, otherwise you will get an exception.
	 *
	 * @param Folder $folderToMove The folder to move.
	 * @param Folder $targetParentFolder The target parent folder
	 * @param string $newFolderName
	 * @param string $conflictMode  How to handle conflicts; one of "overrideExistingFile", "renameNewFolder", "cancel
	 *
	 * @throws \Exception|\TYPO3\CMS\Core\Exception
	 * @throws \InvalidArgumentException
	 * @return Folder
	 */
	public function moveFolder(Folder $folderToMove, Folder $targetParentFolder, $newFolderName = NULL, $conflictMode = 'renameNewFolder') {
		// TODO add tests
		$this->assureFolderMovePermissions($folderToMove, $targetParentFolder);
		$sourceStorage = $folderToMove->getStorage();
		$returnObject = NULL;
		$sanitizedNewFolderName = $this->driver->sanitizeFileName($newFolderName ?: $folderToMove->getName());
		// TODO check if folder already exists in $targetParentFolder, handle this conflict then
		$this->emitPreFolderMoveSignal($folderToMove, $targetParentFolder, $sanitizedNewFolderName);
		// Get all file objects now so we are able to update them after moving the folder
		$fileObjects = $this->getAllFileObjectsInFolder($folderToMove);
		if ($sourceStorage === $this) {
			$fileMappings = $this->driver->moveFolderWithinStorage($folderToMove->getIdentifier(), $targetParentFolder->getIdentifier(), $sanitizedNewFolderName);
		} else {
			$fileMappings = $this->moveFolderBetweenStorages($folderToMove, $targetParentFolder, $sanitizedNewFolderName);
		}
		// Update the identifier and storage of all file objects
		foreach ($fileObjects as $oldIdentifier => $fileObject) {
			$newIdentifier = $fileMappings[$oldIdentifier];
			$fileObject->updateProperties(array('storage' => $this->getUid(), 'identifier' => $newIdentifier));
			$this->getIndexer()->updateIndexEntry($fileObject);
		}
		$returnObject = $this->getFolder($fileMappings[$folderToMove->getIdentifier()]);
		$this->emitPostFolderMoveSignal($folderToMove, $targetParentFolder, $returnObject->getName());
		return $returnObject;
	}

	/**
	 * Moves the given folder from a different storage to the target folder in this storage.
	 *
	 * @param Folder $folderToMove
	 * @param Folder $targetParentFolder
	 * @param string $newFolderName
	 *
	 * @return bool
	 * @throws \RuntimeException
	 */
	protected function moveFolderBetweenStorages(Folder $folderToMove, Folder $targetParentFolder, $newFolderName) {
		throw new \RuntimeException('Not yet implemented');
	}

	/**
	 * Copies a folder.
	 *
	 * @param FolderInterface $folderToCopy The folder to copy
	 * @param FolderInterface $targetParentFolder The target folder
	 * @param string $newFolderName
	 * @param string $conflictMode  "overrideExistingFolder", "renameNewFolder", "cancel
	 * @return Folder The new (copied) folder object
	 */
	public function copyFolder(FolderInterface $folderToCopy, FolderInterface $targetParentFolder, $newFolderName = NULL, $conflictMode = 'renameNewFolder') {
		// TODO implement the $conflictMode handling
		$this->assureFolderCopyPermissions($folderToCopy, $targetParentFolder);
		$returnObject = NULL;
		$sanitizedNewFolderName = $this->driver->sanitizeFileName($newFolderName ?: $folderToCopy->getName());
		if ($folderToCopy instanceof Folder && $targetParentFolder instanceof Folder) {
			$this->emitPreFolderCopySignal($folderToCopy, $targetParentFolder, $sanitizedNewFolderName);
		}
		$sourceStorage = $folderToCopy->getStorage();
		// call driver method to move the file
		// that also updates the file object properties
		try {
			if ($sourceStorage === $this) {
				$this->driver->copyFolderWithinStorage($folderToCopy->getIdentifier(), $targetParentFolder->getIdentifier(), $sanitizedNewFolderName);
				$returnObject = $this->getFolder($targetParentFolder->getSubfolder($sanitizedNewFolderName)->getIdentifier());
			} else {
				$this->copyFolderBetweenStorages($folderToCopy, $targetParentFolder, $sanitizedNewFolderName);
			}
		} catch (\TYPO3\CMS\Core\Exception $e) {
			echo $e->getMessage();
		}
		$this->emitPostFolderCopySignal($folderToCopy, $targetParentFolder, $returnObject->getName());
		return $returnObject;
	}

	/**
	 * Copies a folder between storages.
	 *
	 * @param Folder $folderToCopy
	 * @param Folder $targetParentFolder
	 * @param string $newFolderName
	 *
	 * @return bool
	 * @throws \RuntimeException
	 */
	protected function copyFolderBetweenStorages(Folder $folderToCopy, Folder $targetParentFolder, $newFolderName) {
		throw new \RuntimeException('Not yet implemented.');
	}

	/**
	 * Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::folder_move()
	 *
	 * @param Folder $folderObject
	 * @param string $newName
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 * @return Folder
	 */
	public function renameFolder($folderObject, $newName) {

		// Renaming the folder should check if the parent folder is writable
		// We cannot do this however because we cannot extract the parent folder from a folder currently
		if (!$this->checkFolderActionPermission('rename', $folderObject)) {
			throw new Exception\InsufficientUserPermissionsException('You are not allowed to rename the folder "' . $folderObject->getIdentifier() . '\'', 1357811441);
		}

		$sanitizedNewName = $this->driver->sanitizeFileName($newName);
		$returnObject = NULL;
		if ($this->driver->folderExistsInFolder($sanitizedNewName, $folderObject->getIdentifier())) {
			throw new \InvalidArgumentException('The folder ' . $sanitizedNewName . ' already exists in folder ' . $folderObject->getIdentifier(), 1325418870);
		}

		$this->emitPreFolderRenameSignal($folderObject, $sanitizedNewName);

		$fileObjects = $this->getAllFileObjectsInFolder($folderObject);
		$fileMappings = $this->driver->renameFolder($folderObject->getIdentifier(), $sanitizedNewName);
		// Update the identifier of all file objects
		foreach ($fileObjects as $oldIdentifier => $fileObject) {
			$newIdentifier = $fileMappings[$oldIdentifier];
			$fileObject->updateProperties(array('identifier' => $newIdentifier));
			$this->getIndexer()->updateIndexEntry($fileObject);
		}
		$returnObject = $this->getFolder($fileMappings[$folderObject->getIdentifier()]);

		$this->emitPostFolderRenameSignal($folderObject, $returnObject->getName());

		return $returnObject;
	}

	/**
	 * Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::folder_delete()
	 *
	 * @param Folder $folderObject
	 * @param bool $deleteRecursively
	 * @throws \RuntimeException
	 * @return bool
	 */
	public function deleteFolder($folderObject, $deleteRecursively = FALSE) {
		$isEmpty = $this->driver->isFolderEmpty($folderObject->getIdentifier());
		$this->assureFolderDeletePermission($folderObject, ($deleteRecursively && !$isEmpty));
		if (!$isEmpty && !$deleteRecursively) {
			throw new \RuntimeException('Could not delete folder "' . $folderObject->getIdentifier() . '" because it is not empty.', 1325952534);
		}

		$this->emitPreFolderDeleteSignal($folderObject);

		$result = $this->driver->deleteFolder($folderObject->getIdentifier(), $deleteRecursively);

		$this->emitPostFolderDeleteSignal($folderObject);

		return $result;
	}

	/**
	 * Returns a list of folders in a given path.
	 *
	 * @param string $path The path to list
	 * @param int $start The position to start the listing; if not set or 0, start from the beginning
	 * @param int $numberOfItems The number of items to list; if not set, return all items
	 * @param bool $useFilters If FALSE, the list is returned without any filtering; otherwise, the filters defined for this storage are used.
	 * @return array Information about the folders found.
	 * @deprecated since TYPO3 6.2, will be removed to versions later
	 */
	public function getFolderList($path, $start = 0, $numberOfItems = 0, $useFilters = TRUE) {
		GeneralUtility::logDeprecatedFunction();
		// Permissions are checked in $this->fetchFolderListFromDriver()
		$filters = $useFilters === TRUE ? $this->fileAndFolderNameFilters : array();
		return $this->fetchFolderListFromDriver($path, $start, $numberOfItems, $filters);
	}

	/**
	 * @param Folder $folder
	 * @param int $start
	 * @param int $maxNumberOfItems
	 * @param bool $useFilters
	 * @param bool $recursive
	 *
	 * @return Folder[]
	 */
	public function getFoldersInFolder(Folder $folder, $start = 0, $maxNumberOfItems = 0, $useFilters = TRUE, $recursive = FALSE) {
		$filters = $useFilters == TRUE ? $this->fileAndFolderNameFilters : array();
		$folderIdentifiers = $this->driver->getFoldersInFolder($folder->getIdentifier(), $start, $maxNumberOfItems, $recursive, $filters);

		$processingIdentifier = $this->getProcessingFolder()->getIdentifier();
		if (isset($folderIdentifiers[$processingIdentifier])) {
			unset($folderIdentifiers[$processingIdentifier]);
		}
		$folders = array();
		foreach ($folderIdentifiers as $folderIdentifier) {
			$folders[$folderIdentifier] = $this->getFolder($folderIdentifier, TRUE);
		}
		return $folders;
	}

	/**
	 * @param $path
	 * @param int $start
	 * @param int $numberOfItems
	 * @param array $folderFilterCallbacks
	 * @param bool $recursive
	 * @return array
	 * @deprecated since 6.2, will be removed 2 versions later
	 */
	public function fetchFolderListFromDriver($path, $start = 0, $numberOfItems = 0, array $folderFilterCallbacks = array(), $recursive = FALSE) {
		GeneralUtility::logDeprecatedFunction();
		// This also checks for access to that path and throws exceptions accordingly
		$parentFolder = $this->getFolder($path);
		if ($parentFolder === NULL) {
			return array();
		}
		$folders = $this->getFoldersInFolder($parentFolder, $start, $numberOfItems, count($folderFilterCallbacks) > 0, $recursive);
		$folderInfo = array();
		foreach ($folders as $folder) {
			$folderInfo[$folder->getIdentifier()] = array(
				'name' => $folder->getName(),
				'identifier' => $folder->getIdentifier()
			);
		}
		return $folderInfo;
	}

	/**
	 * Returns TRUE if the specified folder exists.
	 *
	 * @param string $identifier
	 * @return bool
	 */
	public function hasFolder($identifier) {
		$this->assureFolderReadPermission();
		return $this->driver->folderExists($identifier);
	}

	/**
	 * Checks if the given file exists in the given folder
	 *
	 * @param string $folderName
	 * @param Folder $folder
	 * @return bool
	 */
	public function hasFolderInFolder($folderName, Folder $folder) {
		$this->assureFolderReadPermission($folder);
		return $this->driver->folderExistsInFolder($folderName, $folder->getIdentifier());
	}

	/**
	 * Creates a new folder.
	 *
	 * previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::func_newfolder()
	 *
	 * @param string $folderName The new folder name
	 * @param Folder $parentFolder (optional) the parent folder to create the new folder inside of. If not given, the root folder is used
	 *
	 * @throws Exception\InsufficientFolderWritePermissionsException
	 * @throws \InvalidArgumentException
	 * @return Folder The new folder object
	 */
	public function createFolder($folderName, Folder $parentFolder = NULL) {
		if ($parentFolder === NULL) {
			$parentFolder = $this->getRootLevelFolder();
		} elseif (!$this->driver->folderExists($parentFolder->getIdentifier())) {
			throw new \InvalidArgumentException('Parent folder "' . $parentFolder->getIdentifier() . '" does not exist.', 1325689164);
		}
		if (!$this->checkFolderActionPermission('add', $parentFolder)) {
			throw new Exception\InsufficientFolderWritePermissionsException('You are not allowed to create directories in the folder "' . $parentFolder->getIdentifier() . '"', 1323059807);
		}

		$this->emitPreFolderAddSignal($parentFolder, $folderName);

		$newFolder = $this->getDriver()->createFolder($folderName, $parentFolder->getIdentifier(), TRUE);
		$newFolder = $this->getFolder($newFolder);

		$this->emitPostFolderAddSignal($newFolder);

		return $newFolder;
	}

	/**
	 * Returns the default folder where new files are stored if no other folder is given.
	 *
	 * @return Folder
	 */
	public function getDefaultFolder() {
		return $this->getFolder($this->driver->getDefaultFolder());
	}

	/**
	 * @param string $identifier
	 * @param bool $returnInaccessibleFolderObject
	 *
	 * @return Folder
	 * @throws \Exception
	 * @throws Exception\InsufficientFolderAccessPermissionsException
	 */
	public function getFolder($identifier, $returnInaccessibleFolderObject = FALSE) {
		$data = $this->driver->getFolderInfoByIdentifier($identifier);
		$folder = ResourceFactory::getInstance()->createFolderObject($this, $data['identifier'], $data['name']);

		try {
			$this->assureFolderReadPermission($folder);
		} catch (Exception\InsufficientFolderAccessPermissionsException $e) {
			$folder = NULL;
			if ($returnInaccessibleFolderObject) {
				// if parent folder is readable return inaccessible folder object
				$parentPermissions = $this->driver->getPermissions($this->driver->getParentFolderIdentifierOfIdentifier($identifier));
				if ($parentPermissions['r']) {
					$folder = GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Resource\\InaccessibleFolder', $this, $data['identifier'], $data['name']
					);
				}
			}

			if ($folder === NULL) {
				throw $e;
			}
		}
		return $folder;
	}

	/**
	 * @param string $identifier
	 * @return bool
	 */
	public function isWithinProcessingFolder($identifier) {
		return $this->driver->isWithin($this->getProcessingFolder()->getIdentifier(), $identifier);
	}

	/**
	 * Returns the folders on the root level of the storage
	 * or the first mount point of this storage for this user.
	 *
	 * @return Folder
	 */
	public function getRootLevelFolder() {
		if (count($this->fileMounts)) {
			$mount = reset($this->fileMounts);
			return $mount['folder'];
		} else {
			return ResourceFactory::getInstance()->createFolderObject($this, $this->driver->getRootLevelFolder(), '');
		}
	}

	/**
	 * Emits file pre-add signal.
	 *
	 * @param string $targetFileName
	 * @param Folder $targetFolder
	 * @param string $sourceFilePath
	 * @return void
	 */
	protected function emitPreFileAddSignal(&$targetFileName, Folder $targetFolder, $sourceFilePath) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFileAdd, array(&$targetFileName, $targetFolder, $sourceFilePath, $this, $this->driver));
	}

	/**
	 * Emits the file post-add signal.
	 *
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @return void
	 */
	protected function emitPostFileAddSignal(FileInterface $file, Folder $targetFolder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFileAdd, array($file, $targetFolder));
	}

	/**
	 * Emits file pre-copy signal.
	 *
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @return void
	 */
	protected function emitPreFileCopySignal(FileInterface $file, Folder $targetFolder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFileCopy, array($file, $targetFolder));
	}

	/**
	 * Emits the file post-copy signal.
	 *
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @return void
	 */
	protected function emitPostFileCopySignal(FileInterface $file, Folder $targetFolder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFileCopy, array($file, $targetFolder));
	}

	/**
	 * Emits the file pre-move signal.
	 *
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @return void
	 */
	protected function emitPreFileMoveSignal(FileInterface $file, Folder $targetFolder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFileMove, array($file, $targetFolder));
	}

	/**
	 * Emits the file post-move signal.
	 *
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @return void
	 */
	protected function emitPostFileMoveSignal(FileInterface $file, Folder $targetFolder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFileMove, array($file, $targetFolder));
	}

	/**
	 * Emits the file pre-rename signal
	 *
	 * @param FileInterface $file
	 * @param $targetFolder
	 * @return void
	 */
	protected function emitPreFileRenameSignal(FileInterface $file, $targetFolder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFileRename, array($file, $targetFolder));
	}

	/**
	 * Emits the file post-rename signal.
	 *
	 * @param FileInterface $file
	 * @param $targetFolder
	 * @return void
	 */
	protected function emitPostFileRenameSignal(FileInterface $file, $targetFolder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFileRename, array($file, $targetFolder));
	}

	/**
	 * Emits the file pre-replace signal.
	 *
	 * @param FileInterface $file
	 * @param $localFilePath
	 * @return void
	 */
	protected function emitPreFileReplaceSignal(FileInterface $file, $localFilePath) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFileReplace, array($file, $localFilePath));
	}

	/**
	 * Emits the file post-replace signal
	 *
	 * @param FileInterface $file
	 * @param $localFilePath
	 * @return void
	 */
	protected function emitPostFileReplaceSignal(FileInterface $file, $localFilePath) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFileReplace, array($file, $localFilePath));
	}

	/**
	 * Emits the file pre-deletion signal.
	 *
	 * @param FileInterface $file
	 * @return void
	 */
	protected function emitPreFileDeleteSignal(FileInterface $file) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFileDelete, array($file));
	}

	/**
	 * Emits the file post-deletion signal
	 *
	 * @param FileInterface $file
	 * @return void
	 */
	protected function emitPostFileDeleteSignal(FileInterface $file) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFileDelete, array($file));
	}

	/**
	 * Emits the folder pre-add signal.
	 *
	 * @param Folder $targetFolder
	 * @param string $name
	 * @return void
	 */
	protected function emitPreFolderAddSignal(Folder $targetFolder, $name) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFolderAdd, array($targetFolder, $name));
	}

	/**
	 * Emits the folder post-add signal.
	 *
	 * @param Folder $folder
	 * @return void
	 */
	protected function emitPostFolderAddSignal(Folder $folder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFolderAdd, array($folder));
	}

	/**
	 * Emits the folder pre-copy signal.
	 *
	 * @param Folder $folder
	 * @param Folder $targetFolder
	 * @param $newName
	 * @return void
	 */
	protected function emitPreFolderCopySignal(Folder $folder, Folder $targetFolder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFolderCopy, array($folder, $targetFolder, $newName));
	}

	/**
	 * Emits the folder post-copy signal.
	 *
	 * @param Folder $folder
	 * @param Folder $targetFolder
	 * @param $newName
	 * @return void
	 */
	protected function emitPostFolderCopySignal(Folder $folder, Folder $targetFolder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFolderCopy, array($folder, $targetFolder, $newName));
	}

	/**
	 * Emits the folder pre-move signal.
	 *
	 * @param Folder $folder
	 * @param Folder $targetFolder
	 * @param $newName
	 * @return void
	 */
	protected function emitPreFolderMoveSignal(Folder $folder, Folder $targetFolder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFolderMove, array($folder, $targetFolder, $newName));
	}

	/**
	 * Emits the folder post-move signal.
	 *
	 * @param Folder $folder
	 * @param Folder $targetFolder
	 * @param $newName
	 * @return void
	 */
	protected function emitPostFolderMoveSignal(Folder $folder, Folder $targetFolder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFolderMove, array($folder, $targetFolder, $newName));
	}

	/**
	 * Emits the folder pre-rename signal.
	 *
	 * @param Folder $folder
	 * @param $newName
	 * @return void
	 */
	protected function emitPreFolderRenameSignal(Folder $folder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFolderRename, array($folder, $newName));
	}

	/**
	 * Emits the folder post-rename signal.
	 *
	 * @param Folder $folder
	 * @param $newName
	 * @return void
	 */
	protected function emitPostFolderRenameSignal(Folder $folder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFolderRename, array($folder, $newName));
	}

	/**
	 * Emits the folder pre-deletion signal.
	 *
	 * @param Folder $folder
	 * @return void
	 */
	protected function emitPreFolderDeleteSignal(Folder $folder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFolderDelete, array($folder));
	}

	/**
	 * Emits folder post-deletion signal..
	 *
	 * @param Folder $folder
	 * @return void
	 */
	protected function emitPostFolderDeleteSignal(Folder $folder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFolderDelete, array($folder));
	}

	/**
	 * Emits file pre-processing signal when generating a public url for a file or folder.
	 *
	 * @param ResourceInterface $resourceObject
	 * @param bool $relativeToCurrentScript
	 * @param array $urlData
	 */
	protected function emitPreGeneratePublicUrl(ResourceInterface $resourceObject, $relativeToCurrentScript, array $urlData) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreGeneratePublicUrl, array($this, $this->driver, $resourceObject, $relativeToCurrentScript, $urlData));
	}

	/**
	 * Returns the destination path/fileName of a unique fileName/foldername in that path.
	 * If $theFile exists in $theDest (directory) the file have numbers appended up to $this->maxNumber. Hereafter a unique string will be appended.
	 * This function is used by fx. TCEmain when files are attached to records and needs to be uniquely named in the uploads/* folders
	 *
	 * @param Folder $folder
	 * @param string $theFile The input fileName to check
	 * @param bool $dontCheckForUnique If set the fileName is returned with the path prepended without checking whether it already existed!
	 *
	 * @throws \RuntimeException
	 * @return string A unique fileName inside $folder, based on $theFile.
	 * @see \TYPO3\CMS\Core\Utility\File\BasicFileUtility::getUniqueName()
	 */
	protected function getUniqueName(Folder $folder, $theFile, $dontCheckForUnique = FALSE) {
		static $maxNumber = 99, $uniqueNamePrefix = '';
		// Fetches info about path, name, extention of $theFile
		$origFileInfo = GeneralUtility::split_fileref($theFile);
		// Adds prefix
		if ($uniqueNamePrefix) {
			$origFileInfo['file'] = $uniqueNamePrefix . $origFileInfo['file'];
			$origFileInfo['filebody'] = $uniqueNamePrefix . $origFileInfo['filebody'];
		}
		// Check if the file exists and if not - return the fileName...
		$fileInfo = $origFileInfo;
		// The destinations file
		$theDestFile = $fileInfo['file'];
		// If the file does NOT exist we return this fileName
		if (!$this->driver->fileExistsInFolder($theDestFile, $folder->getIdentifier()) || $dontCheckForUnique) {
			return $theDestFile;
		}
		// Well the fileName in its pure form existed. Now we try to append
		// numbers / unique-strings and see if we can find an available fileName
		// This removes _xx if appended to the file
		$theTempFileBody = preg_replace('/_[0-9][0-9]$/', '', $origFileInfo['filebody']);
		$theOrigExt = $origFileInfo['realFileext'] ? '.' . $origFileInfo['realFileext'] : '';
		for ($a = 1; $a <= $maxNumber + 1; $a++) {
			// First we try to append numbers
			if ($a <= $maxNumber) {
				$insert = '_' . sprintf('%02d', $a);
			} else {
				$insert = '_' . substr(md5(uniqId('')), 0, 6);
			}
			$theTestFile = $theTempFileBody . $insert . $theOrigExt;
			// The destinations file
			$theDestFile = $theTestFile;
			// If the file does NOT exist we return this fileName
			if (!$this->driver->fileExistsInFolder($theDestFile, $folder->getIdentifier())) {
				return $theDestFile;
			}
		}
		throw new \RuntimeException('Last possible name "' . $theDestFile . '" is already taken.', 1325194291);
	}

	/**
	 * Get the SignalSlot dispatcher.
	 *
	 * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected function getSignalSlotDispatcher() {
		if (!isset($this->signalSlotDispatcher)) {
			$this->signalSlotDispatcher = $this->getObjectManager()->get('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
		}
		return $this->signalSlotDispatcher;
	}

	/**
	 * Gets the ObjectManager.
	 *
	 * @return \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected function getObjectManager() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
	}

	/**
	 * @return ResourceFactory
	 */
	protected function getFileFactory() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
	}

	/**
	 * @return \TYPO3\CMS\Core\Resource\Index\FileIndexRepository
	 */
	protected function getFileIndexRepository() {
		return FileIndexRepository::getInstance();
	}

	/**
	 * @return Service\FileProcessingService
	 */
	protected function getFileProcessingService() {
		if (!$this->fileProcessingService) {
			$this->fileProcessingService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Service\\FileProcessingService', $this, $this->driver);
		}
		return $this->fileProcessingService;
	}

	/**
	 * Gets the role of a folder.
	 *
	 * @param FolderInterface $folder Folder object to get the role from
	 * @return string The role the folder has
	 */
	public function getRole(FolderInterface $folder) {
		$folderRole = FolderInterface::ROLE_DEFAULT;

		if (method_exists($this->driver, 'getRole')) {
			$folderRole = $this->driver->getRole($folder->getIdentifier());
		}

		if ($folder->getIdentifier() === $this->getProcessingFolder()->getIdentifier()) {
			$folderRole = FolderInterface::ROLE_PROCESSING;
		}

		return $folderRole;
	}

	/**
	 * Getter function to return the folder where the files can
	 * be processed. Does not check for access rights here.
	 *
	 * @return Folder
	 */
	public function getProcessingFolder() {
		if (!isset($this->processingFolder)) {
			$processingFolder = self::DEFAULT_ProcessingFolder;
			if (!empty($this->storageRecord['processingfolder'])) {
				$processingFolder = $this->storageRecord['processingfolder'];
			}
			if ($this->driver->folderExists($processingFolder) === FALSE) {
				$this->processingFolder = $this->createFolder($processingFolder);
			} else {
				$data = $this->driver->getFolderInfoByIdentifier($processingFolder);
				$this->processingFolder = ResourceFactory::getInstance()->createFolderObject($this, $data['identifier'], $data['name']);
			}
		}
		return $this->processingFolder;
	}

	/**
	 * Gets the driver Type configured for this storage.
	 *
	 * @return string
	 */
	public function getDriverType() {
		return $this->storageRecord['driver'];
	}

	/**
	 * Gets the Indexer.
	 *
	 * @return \TYPO3\CMS\Core\Resource\Index\Indexer
	 */
	protected function getIndexer() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\Indexer', $this);
	}

	/**
	 * @param bool $isDefault
	 * @return void
	 */
	public function setDefault($isDefault) {
		$this->isDefault = (bool)$isDefault;
	}

	/**
	 * @return bool
	 */
	public function isDefault() {
		return $this->isDefault;
	}
}
