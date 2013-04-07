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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
class ResourceStorage {

	const SIGNAL_PreProcessConfiguration = 'preProcessConfiguration';
	const SIGNAL_PostProcessConfiguration = 'postProcessConfiguration';
	const SIGNAL_PreFileCopy = 'preFileCopy';
	const SIGNAL_PostFileCopy = 'postFileCopy';
	const SIGNAL_PreFileMove = 'preFileMove';
	const SIGNAL_PostFileMove = 'postFileMove';
	const SIGNAL_PreFileDelete = 'preFileDelete';
	const SIGNAL_PostFileDelete = 'postFileDelete';
	const SIGNAL_PreFileRename = 'preFileRename';
	const SIGNAL_PostFileRename = 'postFileRename';
	const SIGNAL_PreFileReplace = 'preFileReplace';
	const SIGNAL_PostFileReplace = 'postFileReplace';
	const SIGNAL_PreFolderCopy = 'preFolderCopy';
	const SIGNAL_PostFolderCopy = 'postFolderCopy';
	const SIGNAL_PreFolderMove = 'preFolderMove';
	const SIGNAL_PostFolderMove = 'postFolderMove';
	const SIGNAL_PreFolderDelete = 'preFolderDelete';
	const SIGNAL_PostFolderDelete = 'postFolderDelete';
	const SIGNAL_PreFolderRename = 'preFolderRename';
	const SIGNAL_PostFolderRename = 'postFolderRename';
	const SIGNAL_PreGeneratePublicUrl = 'preGeneratePublicUrl';
	/**
	 * The storage driver instance belonging to this storage.
	 *
	 * @var Driver\AbstractDriver
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
	 * The base URI to this storage.
	 *
	 * @var string
	 */
	protected $baseUri;

	/**
	 * @var Service\FileProcessingService
	 */
	protected $fileProcessingService;

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
	 * @var integer
	 */
	protected $capabilities;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * Capability for being browsable by (backend) users
	 */
	const CAPABILITY_BROWSABLE = 1;
	/**
	 * Capability for publicly accessible storages (= accessible from the web)
	 */
	const CAPABILITY_PUBLIC = 2;
	/**
	 * Capability for writable storages. This only signifies writability in
	 * general - this might also be further limited by configuration.
	 */
	const CAPABILITY_WRITABLE = 4;
	/**
	 * Name of the default processing folder
	 */
	const DEFAULT_ProcessingFolder = '_processed_';
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
	 * The filters used for the files and folder names.
	 *
	 * @var array
	 */
	protected $fileAndFolderNameFilters = array();

	/**
	 * Constructor for a storage object.
	 *
	 * @param Driver\AbstractDriver $driver
	 * @param array $storageRecord The storage record row from the database
	 */
	public function __construct(Driver\AbstractDriver $driver, array $storageRecord) {
		$this->storageRecord = $storageRecord;
		$this->configuration = ResourceFactory::getInstance()->convertFlexFormDataToConfigurationArray($storageRecord['configuration']);
		$this->driver = $driver;
		$this->driver->setStorage($this);
		try {
			$this->driver->processConfiguration();
		} catch (Exception\InvalidConfigurationException $e) {
			// configuration error
			// mark this storage as permanently unusable
			$this->markAsPermanentlyOffline();
		}
		$this->driver->initialize();
		$this->capabilities = ($this->storageRecord['is_browsable'] && $this->driver->hasCapability(self::CAPABILITY_BROWSABLE) ? self::CAPABILITY_BROWSABLE : 0) + ($this->storageRecord['is_public'] && $this->driver->hasCapability(self::CAPABILITY_PUBLIC) ? self::CAPABILITY_PUBLIC : 0) + ($this->storageRecord['is_writable'] && $this->driver->hasCapability(self::CAPABILITY_WRITABLE) ? self::CAPABILITY_WRITABLE : 0);
		// TODO do not set the "public" capability if no public URIs can be generated
		$this->processConfiguration();
		$this->resetFileAndFolderNameFiltersToDefault();
	}

	/**
	 * Gets the configuration
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
	 * Processes the configuration of this storage.
	 *
	 * @throws \InvalidArgumentException If a required configuration option is not set or has an invalid value.
	 * @return void
	 */
	protected function processConfiguration() {
		$this->emitPreProcessConfigurationSignal();
		if (isset($this->configuration['baseUri'])) {
			$this->baseUri = rtrim($this->configuration['baseUri'], '/') . '/';
		}
		$this->emitPostProcessConfigurationSignal();
	}

	/**
	 * Returns the base URI of this storage; all files are reachable via URLs
	 * beginning with this string.
	 *
	 * @return string
	 */
	public function getBaseUri() {
		return $this->baseUri;
	}

	/**
	 * Sets the storage that belongs to this storage.
	 *
	 * @param Driver\AbstractDriver $driver
	 * @return ResourceStorage
	 */
	public function setDriver(Driver\AbstractDriver $driver) {
		$this->driver = $driver;
		return $this;
	}

	/**
	 * Returns the driver object belonging to this storage.
	 *
	 * @return Driver\AbstractDriver
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
		throw new \BadMethodCallException('Function TYPO3\\CMS\\Core\\Resource\\ResourceStorage::getFolderByIdentifier() has been renamed to just getFolder(). Please fix the method call.', 1333754514);
	}

	/**
	 * Deprecated function, don't use it. Will be removed in some later revision.
	 *
	 * @param string $identifier
	 *
	 * @throws \BadMethodCallException
	 */
	public function getFileByIdentifier($identifier) {
		throw new \BadMethodCallException('Function TYPO3\\CMS\\Core\\Resource\\ResourceStorage::getFileByIdentifier() has been renamed to just getFileInfoByIdentifier(). ' . 'Please fix the method call.', 1333754533);
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
	 * Returns the uid of this storage.
	 *
	 * @return integer
	 */
	public function getUid() {
		return (int) $this->storageRecord['uid'];
	}

	/**
	 * Tells whether there are children in this storage
	 *
	 * @return boolean
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
	 * @return integer
	 * @see CAPABILITY_* constants
	 */
	public function getCapabilities() {
		return (int) $this->capabilities;
	}

	/**
	 * Returns TRUE if this storage has the given capability.
	 *
	 * @param int $capability A capability, as defined in a CAPABILITY_* constant
	 * @return boolean
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
	 * @return boolean
	 */
	public function isPublic() {
		return $this->hasCapability(self::CAPABILITY_PUBLIC);
	}

	/**
	 * Returns TRUE if this storage is writable. This is determined by the
	 * driver and the storage configuration; user permissions are not taken into account.
	 *
	 * @return boolean
	 */
	public function isWritable() {
		return $this->hasCapability(self::CAPABILITY_WRITABLE);
	}

	/**
	 * Returns TRUE if this storage is browsable by a (backend) user of TYPO3.
	 *
	 * @return boolean
	 */
	public function isBrowsable() {
		return $this->isOnline() && $this->hasCapability(self::CAPABILITY_BROWSABLE);
	}

	/**
	 * Returns TRUE if this storage is browsable by a (backend) user of TYPO3.
	 *
	 * @return boolean
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
					$registryObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
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
	 * blow the "fuse" and mark the storage as offline
	 * can only be modified by an admin
	 * typically this is only done if the configuration is wrong
	 */
	public function markAsPermanentlyOffline() {
		if ($this->getUid() > 0) {
			// @todo: move this to the storage repository
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_storage', 'uid=' . intval($this->getUid()), array('is_online' => 0));
		}
		$this->storageRecord['is_online'] = 0;
		$this->isOnline = FALSE;
	}

	/**
	 * mark this storage as offline
	 *
	 * non-permanent: this typically happens for remote storages
	 * that are "flaky" and not available all the time
	 * mark this storage as offline for the next 5 minutes
	 *
	 * @return void
	 */
	public function markAsTemporaryOffline() {
		$registryObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
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
		$folderObject = $this->driver->getFolder($folderIdentifier);
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
	 * @param $subject
	 * @return boolean
	 */
	public function isWithinFileMountBoundaries($subject) {
		$isWithinFilemount = TRUE;
		if (is_array($this->fileMounts)) {
			$isWithinFilemount = FALSE;
			if (!$subject) {
				$subject = $this->getRootLevelFolder();
			}
			$identifier = $subject->getIdentifier();

			// Allow access to processing folder
			if ($this->driver->isWithin($this->getProcessingFolder(), $identifier)) {
				$isWithinFilemount = TRUE;
			} else {
				// Check if the identifier of the subject is within at
				// least one of the file mounts
				foreach ($this->fileMounts as $fileMount) {
					if ($this->driver->isWithin($fileMount['folder'], $identifier)) {
						$isWithinFilemount = TRUE;
						break;
					}
				}
			}
		}
		return $isWithinFilemount;
	}

	/**
	 * Sets the user permissions of the storage
	 *
	 * @param array $userPermissions
	 * @return void
	 */
	public function setUserPermissions(array $userPermissions) {
		$this->userPermissions = $userPermissions;
	}

	/**
	 * Check if the ACL settings allow for a certain action
	 * (is a user allowed to read a file or copy a folder)
	 *
	 * @param string $action
	 * @param string $type either File or Folder
	 * @return 	bool
	 */
	public function checkUserActionPermission($action, $type) {
		// TODO decide if we should return TRUE if no permissions are set
		if (!empty($this->userPermissions)) {
			$action = strtolower($action);
			$type = ucfirst(strtolower($type));
			if ($this->userPermissions[$action . $type] == 0) {
				return FALSE;
			} else {
				return TRUE;
			}
		}
		// TODO should the default be really TRUE?
		return TRUE;
	}

	/**
	 * Check if a file operation (= action) is allowed on a
	 * File/Folder/Storage (= subject).
	 *
	 * This method, by design, does not throw exceptions or do logging.
	 * Besides the usage from other methods in this class, it is also used by
	 * the File List UI to check whether an action is allowed and whether action
	 * related UI elements should thus be shown (move icon, edit icon, etc.)
	 *
	 * @param string $action, can be read, write, delete
	 * @param FileInterface $file
	 * @return boolean
	 */
	public function checkFileActionPermission($action, FileInterface $file) {
		// Check 1: Does the user have permission to perform the action? e.g. "readFile"
		if ($this->checkUserActionPermission($action, 'File') === FALSE) {
			return FALSE;
		}
		// Check 2: Does the user has the right to perform the action?
		// (= is he within the file mount borders)
		if (is_array($this->fileMounts) && count($this->fileMounts) && !$this->isWithinFileMountBoundaries($file)) {
			return FALSE;
		}
		$isReadCheck = FALSE;
		if ($action === 'read') {
			$isReadCheck = TRUE;
		}
		$isWriteCheck = FALSE;
		if (in_array($action, array('add', 'edit', 'write', 'upload', 'move', 'rename', 'unzip', 'remove'))) {
			$isWriteCheck = TRUE;
		}
		// Check 3: Check the capabilities of the storage (and the driver)
		if ($isReadCheck && !$this->isBrowsable()) {
			return FALSE;
		}
		if ($isWriteCheck && !$this->isWritable()) {
			return FALSE;
		}
		// Check 4: "File permissions" of the driver
		$filePermissions = $this->driver->getFilePermissions($file);
		if ($isReadCheck && !$filePermissions['r']) {
			return FALSE;
		}
		if ($isWriteCheck && !$filePermissions['w']) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Check if a folder operation (= action) is allowed on a Folder
	 *
	 * This method, by design, does not throw exceptions or do logging.
	 * See the checkFileActionPermission() method above for the reasons.
	 *
	 * @param string $action
	 * @param Folder $folder
	 * @return boolean
	 */
	public function checkFolderActionPermission($action, Folder $folder = NULL) {
		// Check 1: Does the user have permission to perform the action? e.g. "writeFolder"
		if ($this->checkUserActionPermission($action, 'Folder') === FALSE) {
			return FALSE;
		}
		// Check 2: Does the user has the right to perform the action?
		// (= is he within the file mount borders)
		if (is_array($this->fileMounts) && count($this->fileMounts) && !$this->isWithinFileMountBoundaries($folder)) {
			return FALSE;
		}
		$isReadCheck = FALSE;
		if ($action === 'read') {
			$isReadCheck = TRUE;
		}
		$isWriteCheck = FALSE;
		if (in_array($action, array('add', 'move', 'write', 'remove', 'rename'))) {
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
		$folderPermissions = $this->driver->getFolderPermissions($folder);
		if ($isReadCheck && !$folderPermissions['r']) {
			return FALSE;
		}
		if ($isWriteCheck && !$folderPermissions['w']) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * If the fileName is given, check it against the
	 * TYPO3_CONF_VARS[BE][fileDenyPattern] + and if the file extension is allowed
	 *
	 * @param string $fileName Full filename
	 * @return boolean TRUE if extension/filename is allowed
	 */
	protected function checkFileExtensionPermission($fileName) {
		$isAllowed = \TYPO3\CMS\Core\Utility\GeneralUtility::verifyFilenameAgainstDenyPattern($fileName);
		if ($isAllowed) {
			$fileInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($fileName);
			// Set up the permissions for the file extension
			$fileExtensionPermissions = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace'];
			$fileExtensionPermissions['allow'] = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList(strtolower($fileExtensionPermissions['allow']));
			$fileExtensionPermissions['deny'] = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList(strtolower($fileExtensionPermissions['deny']));
			$fileExtension = strtolower($fileInfo['fileext']);
			if ($fileExtension !== '') {
				// If the extension is found amongst the allowed types, we return TRUE immediately
				if ($fileExtensionPermissions['allow'] === '*' || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($fileExtensionPermissions['allow'], $fileExtension)) {
					return TRUE;
				}
				// If the extension is found amongst the denied types, we return FALSE immediately
				if ($fileExtensionPermissions['deny'] === '*' || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($fileExtensionPermissions['deny'], $fileExtension)) {
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

	/********************
	 * FILE ACTIONS
	 ********************/
	/**
	 * Moves a file from the local filesystem to this storage.
	 *
	 * @param string $localFilePath The file on the server's hard disk to add.
	 * @param Folder $targetFolder The target path, without the fileName
	 * @param string $fileName The fileName. If not set, the local file name is used.
	 * @param string $conflictMode possible value are 'cancel', 'replace', 'changeName'
	 *
	 * @throws \InvalidArgumentException
	 * @throws Exception\ExistingTargetFileNameException
	 * @return FileInterface
	 */
	public function addFile($localFilePath, Folder $targetFolder, $fileName = '', $conflictMode = 'changeName') {
		// TODO check permissions (write on target, upload, ...)
		if (!file_exists($localFilePath)) {
			throw new \InvalidArgumentException('File "' . $localFilePath . '" does not exist.', 1319552745);
		}
		$targetFolder = $targetFolder ? $targetFolder : $this->getDefaultFolder();
		$fileName = $fileName ? $fileName : PathUtility::basename($localFilePath);
		if ($conflictMode === 'cancel' && $this->driver->fileExistsInFolder($fileName, $targetFolder)) {
			throw new Exception\ExistingTargetFileNameException('File "' . $fileName . '" already exists in folder ' . $targetFolder->getIdentifier(), 1322121068);
		} elseif ($conflictMode === 'changeName') {
			$fileName = $this->getUniqueName($targetFolder, $fileName);
		}
		// We do not care whether the file exists if $conflictMode is "replace",
		// so just use the name as is in that case
		return $this->driver->addFile($localFilePath, $targetFolder, $fileName);
	}

	/**
	 * Creates a (cryptographic) hash for a file.
	 *
	 * @param FileInterface $fileObject
	 * @param $hash
	 * @return string
	 */
	public function hashFile(FileInterface $fileObject, $hash) {
		return $this->driver->hash($fileObject, $hash);
	}

	/**
	 * Returns a publicly accessible URL for a file.
	 *
	 * WARNING: Access to the file may be restricted by further means, e.g.
	 * some web-based authentication. You have to take care of this yourself.
	 *
	 * @param ResourceInterface $resourceObject The file or folder object
	 * @param bool $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 * @return string
	 */
	public function getPublicUrl(ResourceInterface $resourceObject, $relativeToCurrentScript = FALSE) {
		$publicUrl = NULL;
		// Pre-process the public URL by an accordant slot
		$this->emitPreGeneratePublicUrl($resourceObject, $relativeToCurrentScript, array('publicUrl' => &$publicUrl));
		// If slot did not handle the signal, use the default way to determine public URL
		if ($publicUrl === NULL) {
			$publicUrl = $this->driver->getPublicUrl($resourceObject, $relativeToCurrentScript);
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
	 * @param bool $writable
	 * @return string Path to local file (either original or copied to some temporary local location)
	 */
	public function getFileForLocalProcessing(FileInterface $fileObject, $writable = TRUE) {
		$filePath = $this->driver->getFileForLocalProcessing($fileObject, $writable);
		// @todo: shouldn't this go in the driver? this function is called from the indexing service
		// @todo: and recursively calls itself over and over again, this is left out for now with getModificationTime()
		// touch($filePath, $fileObject->getModificationTime());
		return $filePath;
	}

	/**
	 * Get file by identifier
	 *
	 * @param string $identifier
	 * @return FileInterface
	 */
	public function getFile($identifier) {
		return $this->driver->getFile($identifier);
	}

	/**
	 * Get information about a file
	 *
	 * @param FileInterface $fileObject
	 * @return array
	 */
	public function getFileInfo(FileInterface $fileObject) {
		return $this->driver->getFileInfo($fileObject);
	}

	/**
	 * Get information about a file by its identifier
	 *
	 * @param string $identifier
	 *
	 * @throws \BadMethodCallException
	 * @return array
	 */
	public function getFileInfoByIdentifier($identifier) {
		throw new \BadMethodCallException('The method ResourceStorage::getFileInfoByIdentifier() has been deprecated. Please fix your method call and use getFileInfo with the file object instead.', 1346577887);
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

	public function setFileAndFolderNameFilters(array $filters) {
		$this->fileAndFolderNameFilters = $filters;
		return $this;
	}

	public function addFileAndFolderNameFilter($filter) {
		$this->fileAndFolderNameFilters[] = $filter;
	}

	/**
	 * Returns a list of files in a given path, filtered by some custom filter methods.
	 *
	 * @see getUnfilteredFileList(), getFileListWithDefaultFilters()
	 * @param string $path The path to list
	 * @param integer $start The position to start the listing; if not set or 0, start from the beginning
	 * @param integer $numberOfItems The number of items to list; if not set, return all items
	 * @param bool $useFilters If FALSE, the list is returned without any filtering; otherwise, the filters defined for this storage are used.
	 * @param bool $loadIndexRecords If set to TRUE, the index records for all files are loaded from the database. This can greatly improve performance of this method, especially with a lot of files.
	 * @param boolean $recursive
	 * @return array Information about the files found.
	 */
	// TODO check if we should use a folder object instead of $path
	// TODO add unit test for $loadIndexRecords
	public function getFileList($path, $start = 0, $numberOfItems = 0, $useFilters = TRUE, $loadIndexRecords = TRUE, $recursive = FALSE) {
		$rows = array();
		if ($loadIndexRecords) {
			$rows = $this->getFileRepository()->getFileIndexRecordsForFolder($this->getFolder($path));
		}
		$filters = $useFilters == TRUE ? $this->fileAndFolderNameFilters : array();
		$items = $this->driver->getFileList($path, $start, $numberOfItems, $filters, $rows, $recursive);
		uksort($items, 'strnatcasecmp');
		return $items;
	}

	/**
	 * Returns TRUE if the specified file exists.
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function hasFile($identifier) {
		// @todo: access check?
		return $this->driver->fileExists($identifier);
	}

	/**
	 * Checks if the queried file in the given folder exists.
	 *
	 * @param string $fileName
	 * @param Folder $folder
	 * @return boolean
	 */
	public function hasFileInFolder($fileName, Folder $folder) {
		return $this->driver->fileExistsInFolder($fileName, $folder);
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
		// Check if $file is readable
		if (!$this->checkFileActionPermission('read', $file)) {
			throw new Exception\InsufficientFileReadPermissionsException('Reading file "' . $file->getIdentifier() . '" is not allowed.', 1330121089);
		}
		return $this->driver->getFileContents($file);
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
	 * @return integer The number of bytes written to the file
	 */
	public function setFileContents(AbstractFile $file, $contents) {
			// Check if user is allowed to edit
		if (!$this->checkUserActionPermission('edit', 'File')) {
			throw new Exception\InsufficientUserPermissionsException(('Updating file "' . $file->getIdentifier()) . '" not allowed for user.', 1330121117);
		}
			// Check if $file is writable
		if (!$this->checkFileActionPermission('write', $file)) {
			throw new Exception\InsufficientFileWritePermissionsException('Writing to file "' . $file->getIdentifier() . '" is not allowed.', 1330121088);
		}
			// Call driver method to update the file and update file properties afterwards
		try {
			$result = $this->driver->setFileContents($file, $contents);
			$fileInfo = $this->driver->getFileInfo($file);
			$fileInfo['sha1'] = $this->driver->hash($file, 'sha1');
			$file->updateProperties($fileInfo);
			$this->getFileRepository()->update($file);
		} catch (\RuntimeException $e) {
			throw $e;
		}
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
	 * @throws Exception\InsufficientFolderWritePermissionsException
	 * @return FileInterface The file object
	 */
	public function createFile($fileName, Folder $targetFolderObject) {
		if (!$this->checkFolderActionPermission('add', $targetFolderObject)) {
			throw new Exception\InsufficientFolderWritePermissionsException('You are not allowed to create directories on this storage "' . $targetFolderObject->getIdentifier() . '"', 1323059807);
		}
		return $this->driver->createFile($fileName, $targetFolderObject);
	}

	/**
	 * Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::deleteFile()
	 *
	 * @param $fileObject FileInterface
	 *
	 * @throws Exception\InsufficientFileAccessPermissionsException
	 * @throws Exception\FileOperationErrorException
	 * @return bool TRUE if deletion succeeded
	 */
	public function deleteFile($fileObject) {
		if (!$this->checkFileActionPermission('remove', $fileObject)) {
			throw new Exception\InsufficientFileAccessPermissionsException('You are not allowed to delete the file "' . $fileObject->getIdentifier() . '\'', 1319550425);
		}

		$this->emitPreFileDeleteSignal($fileObject);

		$result = $this->driver->deleteFile($fileObject);
		if ($result === FALSE) {
			throw new Exception\FileOperationErrorException('Deleting the file "' . $fileObject->getIdentifier() . '\' failed.', 1329831691);
		}
		// Mark the file object as deleted
		$fileObject->setDeleted();

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
		$this->emitPreFileCopySignal($file, $targetFolder);
		$this->checkFileCopyPermissions($file, $targetFolder, $targetFileName);
		if ($targetFileName === NULL) {
			$targetFileName = $file->getName();
		}
		// File exists and we should abort, let's abort
		if ($conflictMode === 'cancel' && $targetFolder->hasFile($targetFileName)) {
			throw new Exception\ExistingTargetFileNameException('The target file already exists.', 1320291063);
		}
		// File exists and we should find another name, let's find another one
		if ($conflictMode === 'renameNewFile' && $targetFolder->hasFile($targetFileName)) {
			$targetFileName = $this->getUniqueName($targetFolder, $targetFileName);
		}
		$sourceStorage = $file->getStorage();
		// Call driver method to create a new file from an existing file object,
		// and return the new file object
		try {
			if ($sourceStorage === $this) {
				$newFileObject = $this->driver->copyFileWithinStorage($file, $targetFolder, $targetFileName);
			} else {
				$tempPath = $file->getForLocalProcessing();
				$newFileObject = $this->driver->addFile($tempPath, $targetFolder, $targetFileName);
			}
		} catch (Exception\AbstractFileOperationException $e) {
			throw $e;
		}
		$this->emitPostFileCopySignal($file, $targetFolder);
		return $newFileObject;
	}

	/**
	 * Check if a file has the permission to be uploaded to a Folder/Storage,
	 * if not throw an exception
	 *
	 * @param string $localFilePath the temporary file name from $_FILES['file1']['tmp_name']
	 * @param Folder $targetFolder
	 * @param string $targetFileName the destination file name $_FILES['file1']['name']
	 * @param int $uploadedFileSize
	 *
	 * @throws Exception\InsufficientFolderWritePermissionsException
	 * @throws Exception\UploadException
	 * @throws Exception\IllegalFileExtensionException
	 * @throws Exception\UploadSizeException
	 * @throws Exception\InsufficientUserPermissionsException
	 * @return void
	 */
	protected function checkFileUploadPermissions($localFilePath, $targetFolder, $targetFileName, $uploadedFileSize) {
		// Makes sure the user is allowed to upload
		if (!$this->checkUserActionPermission('upload', 'File')) {
			throw new Exception\InsufficientUserPermissionsException('You are not allowed to upload files to this storage "' . $this->getUid() . '"', 1322112430);
		}
		// Makes sure this is an uploaded file
		if (!is_uploaded_file($localFilePath)) {
			throw new Exception\UploadException('The upload has failed, no uploaded file found!', 1322110455);
		}
		// Max upload size (kb) for files.
		$maxUploadFileSize = \TYPO3\CMS\Core\Utility\GeneralUtility::getMaxUploadFileSize() * 1024;
		if ($uploadedFileSize >= $maxUploadFileSize) {
			throw new Exception\UploadSizeException('The uploaded file exceeds the size-limit of ' . $maxUploadFileSize . ' bytes', 1322110041);
		}
		// Check if targetFolder is writable
		if (!$this->checkFolderActionPermission('write', $targetFolder)) {
			throw new Exception\InsufficientFolderWritePermissionsException('You are not allowed to write to the target folder "' . $targetFolder->getIdentifier() . '"', 1322120356);
		}
		// Check for a valid file extension
		if (!$this->checkFileExtensionPermission($targetFileName)) {
			throw new Exception\IllegalFileExtensionException('Extension of file name is not allowed in "' . $targetFileName . '"!', 1322120271);
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
	protected function checkFileCopyPermissions(FileInterface $file, Folder $targetFolder, $targetFileName) {
		// Check if targetFolder is within this storage, this should never happen
		if ($this->getUid() != $targetFolder->getStorage()->getUid()) {
			throw new Exception('The operation of the folder cannot be called by this storage "' . $this->getUid() . '"', 1319550405);
		}
		// Check if user is allowed to copy
		if (!$this->checkUserActionPermission('copy', 'File')) {
			throw new Exception\InsufficientUserPermissionsException('You are not allowed to copy files to this storage "' . $this->getUid() . '"', 1319550415);
		}
		// Check if $file is readable
		if (!$this->checkFileActionPermission('read', $file)) {
			throw new Exception\InsufficientFileReadPermissionsException('You are not allowed to read the file "' . $file->getIdentifier() . '\'', 1319550425);
		}
		// Check if targetFolder is writable
		if (!$this->checkFolderActionPermission('write', $targetFolder)) {
			throw new Exception\InsufficientFolderWritePermissionsException('You are not allowed to write to the target folder "' . $targetFolder->getIdentifier() . '"', 1319550435);
		}
		// Check for a valid file extension
		if (!$this->checkFileExtensionPermission($targetFileName)) {
			throw new Exception\IllegalFileExtensionException('You are not allowed to copy a file of that type.', 1319553317);
		}
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
	 * @return FileInterface
	 */
	public function moveFile($file, $targetFolder, $targetFileName = NULL, $conflictMode = 'renameNewFile') {
		$this->checkFileMovePermissions($file, $targetFolder);
		if ($targetFileName === NULL) {
			$targetFileName = $file->getName();
		}
		if ($targetFolder->hasFile($targetFileName)) {
			// File exists and we should abort, let's abort
			if ($conflictMode === 'renameNewFile') {
				$targetFileName = $this->getUniqueName($targetFolder, $targetFileName);
			} elseif ($conflictMode === 'cancel') {
				throw new Exception\ExistingTargetFileNameException('The target file already exists', 1329850997);
			}
		}
		$this->emitPreFileMoveSignal($file, $targetFolder);
		$sourceStorage = $file->getStorage();
		// Call driver method to move the file that also updates the file
		// object properties
		try {
			if ($sourceStorage === $this) {
				$newIdentifier = $this->driver->moveFileWithinStorage($file, $targetFolder, $targetFileName);
				$this->updateFile($file, $newIdentifier);
			} else {
				$tempPath = $file->getForLocalProcessing();
				$newIdentifier = $this->driver->addFileRaw($tempPath, $targetFolder, $targetFileName);
				$sourceStorage->driver->deleteFileRaw($file->getIdentifier());
				$this->updateFile($file, $newIdentifier, $this);
			}
		} catch (\TYPO3\CMS\Core\Exception $e) {
			echo $e->getMessage();
		}
		$this->emitPostFileMoveSignal($file, $targetFolder);
		return $file;
	}

	/**
	 * Updates the properties of a file object with some that are freshly
	 * fetched from the driver.
	 *
	 * @param AbstractFile $file
	 * @param string $identifier The identifier of the file. If set, this will overwrite the file object's identifier (use e.g. after moving a file)
	 * @param ResourceStorage $storage
	 * @return void
	 */
	protected function updateFile(AbstractFile $file, $identifier = '', $storage = NULL) {
		if ($identifier === '') {
			$identifier = $file->getIdentifier();
		}
		$fileInfo = $this->driver->getFileInfoByIdentifier($identifier);
		// TODO extend mapping
		$newProperties = array(
			'storage' => $fileInfo['storage'],
			'identifier' => $fileInfo['identifier'],
			'tstamp' => $fileInfo['mtime'],
			'crdate' => $fileInfo['ctime'],
			'mime_type' => $fileInfo['mimetype'],
			'size' => $fileInfo['size'],
			'name' => $fileInfo['name']
		);
		if ($storage !== NULL) {
			$newProperties['storage'] = $storage->getUid();
		}
		$file->updateProperties($newProperties);
		$this->getFileRepository()->update($file);
	}

	/**
	 * Checks for permissions to move a file.
	 *
	 * @throws \RuntimeException
	 * @throws Exception\InsufficientFileReadPermissionsException
	 * @throws Exception\InsufficientFileWritePermissionsException
	 * @throws Exception\InsufficientFolderAccessPermissionsException
	 * @throws Exception\InsufficientUserPermissionsException
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @return void
	 */
	protected function checkFileMovePermissions(FileInterface $file, Folder $targetFolder) {
		// Check if targetFolder is within this storage
		if ($this->getUid() != $targetFolder->getStorage()->getUid()) {
			throw new \RuntimeException();
		}
		// Check if user is allowed to move
		if (!$this->checkUserActionPermission('move', 'File')) {
			throw new Exception\InsufficientUserPermissionsException('You are not allowed to move files to storage "' . $this->getUid() . '"', 1319219349);
		}
		// Check if $file is readable
		if (!$this->checkFileActionPermission('read', $file)) {
			throw new Exception\InsufficientFileReadPermissionsException('You are not allowed to read the file "' . $file->getIdentifier() . '\'', 1319219349);
		}
		// Check if $file is writable
		if (!$this->checkFileActionPermission('write', $file)) {
			throw new Exception\InsufficientFileWritePermissionsException('You are not allowed to move the file "' . $file->getIdentifier() . '\'', 1319219349);
		}
		// Check if targetFolder is writable
		if (!$this->checkFolderActionPermission('write', $targetFolder)) {
			throw new Exception\InsufficientFolderAccessPermissionsException('You are not allowed to write to the target folder "' . $targetFolder->getIdentifier() . '"', 1319219349);
		}
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
	// TODO add $conflictMode setting
	public function renameFile($file, $targetFileName) {
		// The name should be different from the current.
		if ($file->getIdentifier() == $targetFileName) {
			return $file;
		}
		// Check if user is allowed to rename
		if (!$this->checkUserActionPermission('rename', 'File')) {
			throw new Exception\InsufficientUserPermissionsException('You are not allowed to rename files."', 1319219349);
		}
		// Check if $file is readable
		if (!$this->checkFileActionPermission('read', $file)) {
			throw new Exception\InsufficientFileReadPermissionsException('You are not allowed to read the file "' . $file->getIdentifier() . '\'', 1319219349);
		}
		// Check if $file is writable
		if (!$this->checkFileActionPermission('write', $file)) {
			throw new Exception\InsufficientFileWritePermissionsException('You are not allowed to rename the file "' . $file->getIdentifier() . '\'', 1319219349);
		}

		$this->emitPreFileRenameSignal($file, $targetFileName);

		// Call driver method to rename the file that also updates the file
		// object properties
		try {
			$newIdentifier = $this->driver->renameFile($file, $targetFileName);
			$this->updateFile($file, $newIdentifier);
			$this->getFileRepository()->update($file);
		} catch (\RuntimeException $e) {

		}

		$this->emitPostFileRenameSignal($file, $targetFileName);

		return $file;
	}

	/**
	 * Replaces a file with a local file (e.g. a freshly uploaded file)
	 *
	 * @param FileInterface $file
	 * @param string $localFilePath
	 *
	 * @throws \InvalidArgumentException
	 * @return FileInterface
	 */
	public function replaceFile(FileInterface $file, $localFilePath) {
		if (!file_exists($localFilePath)) {
			throw new \InvalidArgumentException('File "' . $localFilePath . '" does not exist.', 1325842622);
		}
		// TODO check permissions
		$this->emitPreFileReplaceSignal($file, $localFilePath);
		$result = $this->driver->replaceFile($file, $localFilePath);
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
		$this->checkFileUploadPermissions($localFilePath, $targetFolder, $targetFileName, $uploadedFileData['size']);
		$resultObject = $this->addFile($localFilePath, $targetFolder, $targetFileName, $conflictMode);
		return $resultObject;
	}

	/********************
	 * FOLDER ACTIONS
	 ********************/
	/**
	 * Returns an array with all file objects in a folder and its subfolders, with the file identifiers as keys.
	 *
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
			foreach ($folder->getFiles() as $file) {
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
	// TODO add tests
	public function moveFolder(Folder $folderToMove, Folder $targetParentFolder, $newFolderName = NULL, $conflictMode = 'renameNewFolder') {
		$sourceStorage = $folderToMove->getStorage();
		$returnObject = NULL;
		if (!$targetParentFolder->getStorage() == $this) {
			throw new \InvalidArgumentException('Cannot move a folder into a folder that does not belong to this storage.', 1325777289);
		}
		$newFolderName = $newFolderName ? $newFolderName : $folderToMove->getName();
		// TODO check if folder already exists in $targetParentFolder, handle this conflict then
		$this->emitPreFolderMoveSignal($folderToMove, $targetParentFolder, $newFolderName);
		// Get all file objects now so we are able to update them after moving the folder
		$fileObjects = $this->getAllFileObjectsInFolder($folderToMove);
		try {
			if ($sourceStorage === $this) {
				$fileMappings = $this->driver->moveFolderWithinStorage($folderToMove, $targetParentFolder, $newFolderName);
			} else {
				$fileMappings = $this->moveFolderBetweenStorages($folderToMove, $targetParentFolder, $newFolderName);
			}
			// Update the identifier and storage of all file objects
			foreach ($fileObjects as $oldIdentifier => $fileObject) {
				$newIdentifier = $fileMappings[$oldIdentifier];
				$fileObject->updateProperties(array('storage' => $this, 'identifier' => $newIdentifier));
				$this->getFileRepository()->update($fileObject);
			}
			$returnObject = $this->getFolder($fileMappings[$folderToMove->getIdentifier()]);
		} catch (\TYPO3\CMS\Core\Exception $e) {
			throw $e;
		}
		$this->emitPostFolderMoveSignal($folderToMove, $targetParentFolder, $newFolderName);
		return $returnObject;
	}

	/**
	 * Moves the given folder from a different storage to the target folder in this storage.
	 *
	 * @param Folder $folderToMove
	 * @param Folder $targetParentFolder
	 * @param string $newFolderName
	 *
	 * @return boolean
	 */
	protected function moveFolderBetweenStorages(Folder $folderToMove, Folder $targetParentFolder, $newFolderName) {
		return $this->getDriver()->moveFolderBetweenStorages($folderToMove, $targetParentFolder, $newFolderName);
	}

	/**
	 * Copy folder
	 *
	 * @param Folder $folderToCopy The folder to copy
	 * @param Folder $targetParentFolder The target folder
	 * @param string $newFolderName
	 * @param string $conflictMode  "overrideExistingFolder", "renameNewFolder", "cancel
	 * @return Folder The new (copied) folder object
	 */
	public function copyFolder(Folder $folderToCopy, Folder $targetParentFolder, $newFolderName = NULL, $conflictMode = 'renameNewFolder') {
		// TODO implement the $conflictMode handling
		// TODO permission checks
		$returnObject = NULL;
		$newFolderName = $newFolderName ? $newFolderName : $folderToCopy->getName();
		$this->emitPreFolderCopySignal($folderToCopy, $targetParentFolder, $newFolderName);
		$sourceStorage = $folderToCopy->getStorage();
		// call driver method to move the file
		// that also updates the file object properties
		try {
			if ($sourceStorage === $this) {
				$this->driver->copyFolderWithinStorage($folderToCopy, $targetParentFolder, $newFolderName);
				$returnObject = $this->getFolder($targetParentFolder->getSubfolder($newFolderName)->getIdentifier());
			} else {
				$this->copyFolderBetweenStorages($folderToCopy, $targetParentFolder, $newFolderName);
			}
		} catch (\TYPO3\CMS\Core\Exception $e) {
			echo $e->getMessage();
		}
		$this->emitPostFolderCopySignal($folderToCopy, $targetParentFolder, $newFolderName);
		return $returnObject;
	}

	/**
	 * Copy folders between storages
	 *
	 * @param Folder $folderToCopy
	 * @param Folder $targetParentFolder
	 * @param string $newFolderName
	 *
	 * @return boolean
	 */
	protected function copyFolderBetweenStorages(Folder $folderToCopy, Folder $targetParentFolder, $newFolderName) {
		return $this->getDriver()->copyFolderBetweenStorages($folderToCopy, $targetParentFolder, $newFolderName);
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
		// TODO unit tests

		if (!$this->checkFolderActionPermission('rename', $folderObject)) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException('You are not allowed to rename the folder "' . $folderObject->getIdentifier() . '\'', 1357811441);
		}

		$returnObject = NULL;
		if ($this->driver->folderExistsInFolder($newName, $folderObject)) {
			throw new \InvalidArgumentException('The folder ' . $newName . ' already exists in folder ' . $folderObject->getIdentifier(), 1325418870);
		}

		$this->emitPreFolderRenameSignal($folderObject, $newName);

		$fileObjects = $this->getAllFileObjectsInFolder($folderObject);
		try {
			$fileMappings = $this->driver->renameFolder($folderObject, $newName);
			// Update the identifier of all file objects
			foreach ($fileObjects as $oldIdentifier => $fileObject) {
				$newIdentifier = $fileMappings[$oldIdentifier];
				$fileObject->updateProperties(array('identifier' => $newIdentifier));
				$this->getFileRepository()->update($fileObject);
			}
			$returnObject = $this->getFolder($fileMappings[$folderObject->getIdentifier()]);
		} catch (\Exception $e) {
			throw $e;
		}

		$this->emitPostFolderRenameSignal($folderObject, $newName);

		return $returnObject;
	}

	/**
	 * Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::folder_delete()
	 *
	 * @param Folder    $folderObject
	 * @param bool $deleteRecursively
	 * @throws \RuntimeException
	 * @throws Exception\InsufficientFileAccessPermissionsException
	 * @return boolean
	 */
	public function deleteFolder($folderObject, $deleteRecursively = FALSE) {
		if (!$this->checkFolderActionPermission('remove', $folderObject)) {
			throw new Exception\InsufficientFileAccessPermissionsException('You are not allowed to access the folder "' . $folderObject->getIdentifier() . '\'', 1323423953);
		}
		if ($this->driver->isFolderEmpty($folderObject) && !$deleteRecursively) {
			throw new \RuntimeException('Could not delete folder "' . $folderObject->getIdentifier() . '" because it is not empty.', 1325952534);
		}

		$this->emitPreFolderDeleteSignal($folderObject);

		$result = $this->driver->deleteFolder($folderObject, $deleteRecursively);

		$this->emitPostFolderDeleteSignal($folderObject);

		return $result;
	}

	/**
	 * Returns a list of folders in a given path.
	 *
	 * @param string $path The path to list
	 * @param integer $start The position to start the listing; if not set or 0, start from the beginning
	 * @param integer $numberOfItems The number of items to list; if not set, return all items
	 * @param boolean $useFilters If FALSE, the list is returned without any filtering; otherwise, the filters defined for this storage are used.
	 * @return array Information about the folders found.
	 */
	public function getFolderList($path, $start = 0, $numberOfItems = 0, $useFilters = TRUE) {
		$filters = $useFilters === TRUE ? $this->fileAndFolderNameFilters : array();
		return $this->fetchFolderListFromDriver($path, $start, $numberOfItems, $filters);
	}

	/**
	 * @param $path
	 * @param int $start
	 * @param int $numberOfItems
	 * @param array $folderFilterCallbacks
	 * @param boolean $recursive
	 * @return array
	 */
	public function fetchFolderListFromDriver($path, $start = 0, $numberOfItems = 0, array $folderFilterCallbacks = array(), $recursive = FALSE) {
		$items = $this->driver->getFolderList($path, $start, $numberOfItems, $folderFilterCallbacks, $recursive);
		if (!empty($items)) {
			// Exclude the _processed_ folder, so it won't get indexed etc
			// The processed folder might be any sub folder in storage
			$processingFolder = $this->getProcessingFolder();
			if ($processingFolder) {
				$processedFolderIdentifier = $this->processingFolder->getIdentifier();
				$processedFolderIdentifier = trim($processedFolderIdentifier, '/');
				$processedFolderIdentifierParts = explode('/', $processedFolderIdentifier);
				$processedFolderName = array_pop($processedFolderIdentifierParts);
				$processedFolderParent = implode('/', $processedFolderIdentifierParts);
				if ($processedFolderParent === trim($path, '/') && isset($items[$processedFolderName])) {
					unset($items[$processedFolderName]);
				}
			}
			uksort($items, 'strnatcasecmp');
		}
		return $items;
	}

	/**
	 * Returns TRUE if the specified folder exists.
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function hasFolder($identifier) {
		return $this->driver->folderExists($identifier);
	}

	/**
	 * Checks if the given file exists in the given folder
	 *
	 * @param string $folderName
	 * @param Folder $folder
	 * @return boolean
	 */
	public function hasFolderInFolder($folderName, Folder $folder) {
		return $this->driver->folderExistsInFolder($folderName, $folder);
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
		}
		if (!$this->driver->folderExists($parentFolder->getIdentifier())) {
			throw new \InvalidArgumentException('Parent folder "' . $parentFolder->getIdentifier() . '" does not exist.', 1325689164);
		}
		if (!$this->checkFolderActionPermission('add', $parentFolder)) {
			throw new Exception\InsufficientFolderWritePermissionsException('You are not allowed to create directories in the folder "' . $parentFolder->getIdentifier() . '"', 1323059807);
		}
		$folderParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('/', $folderName, TRUE);
		foreach ($folderParts as $folder) {
			// TODO check if folder creation succeeded
			if ($this->hasFolderInFolder($folder, $parentFolder)) {
				$parentFolder = $this->driver->getFolderInFolder($folder, $parentFolder);
			} else {
				$parentFolder = $this->driver->createFolder($folder, $parentFolder);
			}
		}
		return $parentFolder;
	}

	/**
	 * Returns the default folder where new files are stored if no other folder is given.
	 *
	 * @return Folder
	 */
	public function getDefaultFolder() {
		return $this->driver->getDefaultFolder();
	}

	/**
	 * @param string $identifier
	 *
	 * @throws Exception\NotInMountPointException
	 * @throws Exception\FolderDoesNotExistException
	 * @return Folder
	 */
	public function getFolder($identifier) {
		if (!$this->driver->folderExists($identifier)) {
			throw new Exception\FolderDoesNotExistException('Folder ' . $identifier . ' does not exist.', 1320575630);
		}
		$folderObject = $this->driver->getFolder($identifier);
		if ($this->fileMounts && !$this->isWithinFileMountBoundaries($folderObject)) {
			throw new Exception\NotInMountPointException('Folder "' . $identifier . '" is not within your mount points.', 1330120649);
		} else {
			return $folderObject;
		}
	}

	/**
	 * Returns the folders on the root level of the storage
	 * or the first mount point of this storage for this user
	 *
	 * @return Folder
	 */
	public function getRootLevelFolder() {
		if (count($this->fileMounts)) {
			$mount = reset($this->fileMounts);
			return $mount['folder'];
		} else {
			return $this->driver->getRootLevelFolder();
		}
	}

	/**
	 * Emits the configuration pre-processing signal
	 *
	 * @return void
	 */
	protected function emitPreProcessConfigurationSignal() {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreProcessConfiguration, array($this));
	}

	/**
	 * Emits the configuration post-processing signal
	 *
	 * @return void
	 */
	protected function emitPostProcessConfigurationSignal() {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostProcessConfiguration, array($this));
	}

	/**
	 * Emits file pre-copy signal
	 *
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @return void
	 */
	protected function emitPreFileCopySignal(FileInterface $file, Folder $targetFolder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFileCopy, array($file, $targetFolder));
	}

	/**
	 * Emits the file post-copy signal
	 *
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @return void
	 */
	protected function emitPostFileCopySignal(FileInterface $file, Folder $targetFolder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFileCopy, array($file, $targetFolder));
	}

	/**
	 * Emits the file pre-move signal
	 *
	 * @param FileInterface $file
	 * @param Folder $targetFolder
	 * @return void
	 */
	protected function emitPreFileMoveSignal(FileInterface $file, Folder $targetFolder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFileMove, array($file, $targetFolder));
	}

	/**
	 * Emits the file post-move signal
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
	 * Emits the file post-rename signal
	 *
	 * @param FileInterface $file
	 * @param $targetFolder
	 * @return void
	 */
	protected function emitPostFileRenameSignal(FileInterface $file, $targetFolder) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFileRename, array($file, $targetFolder));
	}

	/**
	 * Emits the file pre-replace signal
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
	 * Emits the file pre-deletion signal
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
	 * Emits the folder pre-copy signal
	 *
	 * @param Folder $folder
	 * @param Folder $targetFolder
	 * @param $newName
	 * @return void
	 */
	protected function emitPreFolderCopySignal(Folder $folder, Folder $targetFolder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('ResourceStorage', self::SIGNAL_PreFolderCopy, array($folder, $targetFolder));
	}

	/**
	 * Emits the folder post-copy signal
	 *
	 * @param Folder $folder
	 * @param Folder $targetFolder
	 * @param $newName
	 * @return void
	 */
	protected function emitPostFolderCopySignal(Folder $folder, Folder $targetFolder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('ResourceStorage', self::SIGNAL_PostFolderCopy, array($folder, $targetFolder));
	}

	/**
	 * Emits the folder pre-move signal
	 *
	 * @param Folder $folder
	 * @param Folder $targetFolder
	 * @param $newName
	 * @return void
	 */
	protected function emitPreFolderMoveSignal(Folder $folder, Folder $targetFolder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('ResourceStorage', self::SIGNAL_PreFolderMove, array($folder, $targetFolder));
	}

	/**
	 * Emits the folder post-move signal
	 *
	 * @param Folder $folder
	 * @param Folder $targetFolder
	 * @param $newName
	 * @return void
	 */
	protected function emitPostFolderMoveSignal(Folder $folder, Folder $targetFolder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('ResourceStorage', self::SIGNAL_PostFolderMove, array($folder, $targetFolder));
	}

	/**
	 * Emits the folder pre-rename signal
	 *
	 * @param Folder $folder
	 * @param $newName
	 * @return void
	 */
	protected function emitPreFolderRenameSignal(Folder $folder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('ResourceStorage', self::SIGNAL_PreFolderRename, array($folder, $newName));
	}

	/**
	 * Emits the folder post-rename signal
	 *
	 * @param Folder $folder
	 * @param $newName
	 * @return void
	 */
	protected function emitPostFolderRenameSignal(Folder $folder, $newName) {
		$this->getSignalSlotDispatcher()->dispatch('ResourceStorage', self::SIGNAL_PostFolderRename, array($folder, $newName));
	}

	/**
	 * Emits the folder pre-deletion signal
	 *
	 * @param Folder $folder
	 * @return void
	 */
	protected function emitPreFolderDeleteSignal(Folder $folder) {
		$this->getSignalSlotDispatcher()->dispatch('ResourceStorage', self::SIGNAL_PreFolderDelete, array($folder));
	}

	/**
	 * Emits folder postdeletion signal.
	 *
	 * @param Folder $folder
	 * @return void
	 */
	protected function emitPostFolderDeleteSignal(Folder $folder) {
		$this->getSignalSlotDispatcher()->dispatch('ResourceStorage', self::SIGNAL_PostFolderDelete, array($folder));
	}

	/**
	 * Emits file pre-processing signal when generating a public url for a file or folder.
	 *
	 * @param ResourceInterface $resourceObject
	 * @param boolean $relativeToCurrentScript
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
	 * @param boolean $dontCheckForUnique If set the fileName is returned with the path prepended without checking whether it already existed!
	 *
	 * @throws \RuntimeException
	 * @return string A unique fileName inside $folder, based on $theFile.
	 * @see \TYPO3\CMS\Core\Utility\File\BasicFileUtility::getUniqueName()
	 */
	// TODO check if this should be moved back to Folder
	protected function getUniqueName(Folder $folder, $theFile, $dontCheckForUnique = FALSE) {
		static $maxNumber = 99, $uniqueNamePrefix = '';
		// Fetches info about path, name, extention of $theFile
		$origFileInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($theFile);
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
		if (!$this->driver->fileExistsInFolder($theDestFile, $folder) || $dontCheckForUnique) {
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
				// TODO remove constant 6
				$insert = '_' . substr(md5(uniqId('')), 0, 6);
			}
			$theTestFile = $theTempFileBody . $insert . $theOrigExt;
			// The destinations file
			$theDestFile = $theTestFile;
			// If the file does NOT exist we return this fileName
			if (!$this->driver->fileExistsInFolder($theDestFile, $folder)) {
				return $theDestFile;
			}
		}
		throw new \RuntimeException('Last possible name "' . $theDestFile . '" is already taken.', 1325194291);
	}

	/**
	 * Get the SignalSlot dispatcher
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
	 * Get the ObjectManager
	 *
	 * @return \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected function getObjectManager() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
	}

	/**
	 * @return ResourceFactory
	 */
	protected function getFileFactory() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
	}

	/**
	 * @return \TYPO3\CMS\Core\Resource\FileRepository
	 */
	protected function getFileRepository() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
	}

	/**
	 * @return Service\FileProcessingService
	 */
	protected function getFileProcessingService() {
		if (!$this->fileProcessingService) {
			$this->fileProcessingService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Service\\FileProcessingService', $this, $this->driver);
		}
		return $this->fileProcessingService;
	}

	/**
	 * Gets the role of a folder
	 *
	 * @param FolderInterface $folder Folder object to get the role from
	 * @return string The role the folder has
	 */
	public function getRole(FolderInterface $folder) {
		$folderRole = FolderInterface::ROLE_DEFAULT;

		if (method_exists($this->driver, 'getRole')) {
			$folderRole = $this->driver->getRole($folder);
		}

		if ($folder->getIdentifier() === $this->getProcessingFolder()->getIdentifier()) {
			$folderRole = FolderInterface::ROLE_PROCESSING;
		}

		return $folderRole;
	}

	/**
	 * Getter function to return the folder where the files can
	 * be processed. does not check for access rights here
	 *
	 * @todo check if we need to implement "is writable" capability
	 * @return Folder the processing folder, can be empty as well, if the storage doesn't have a processing folder
	 */
	public function getProcessingFolder() {
		if (!isset($this->processingFolder)) {
			$processingFolder = self::DEFAULT_ProcessingFolder;
			if (!empty($this->storageRecord['processingfolder'])) {
				$processingFolder = $this->storageRecord['processingfolder'];
			}
			$processingFolder = '/' . trim($processingFolder, '/') . '/';
			// this way, we also worry about deeplinked folders like typo3temp/_processed_
			if ($this->driver->folderExists($processingFolder) === FALSE) {
				$processingFolderParts = explode('/', $processingFolder);
				$parentFolder = $this->driver->getRootLevelFolder();
				foreach ($processingFolderParts as $folderPart) {
					if ($folderPart === '') {
						continue;
					}
					if (!$this->driver->folderExistsInFolder($folderPart, $parentFolder)) {
						$parentFolder = $this->driver->createFolder($folderPart, $parentFolder);
					} else {
						$parentFolder = $parentFolder->getSubfolder($folderPart);
					}
				}
			}
			$this->processingFolder = $this->driver->getFolder($processingFolder);
		}
		return $this->processingFolder;
	}
}

?>
