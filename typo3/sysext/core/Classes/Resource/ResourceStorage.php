<?php
namespace TYPO3\CMS\Core\Resource;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InvalidTargetFolderException;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

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
 */
class ResourceStorage implements ResourceStorageInterface
{
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
     * @var bool
     */
    protected $evaluatePermissions = false;

    /**
     * User filemounts, added as an array, and used as filters
     *
     * @var array
     */
    protected $fileMounts = [];

    /**
     * The file permissions of the user (and their group) merged together and
     * available as an array
     *
     * @var array
     */
    protected $userPermissions = [];

    /**
     * The capabilities of this storage as defined in the storage record.
     * Also see the CAPABILITY_* constants below
     *
     * @var int
     */
    protected $capabilities;

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var Folder
     */
    protected $processingFolder;

    /**
     * All processing folders of this storage used in any storage
     *
     * @var Folder[]
     */
    protected $processingFolders;

    /**
     * whether this storage is online or offline in this request
     *
     * @var bool
     */
    protected $isOnline = null;

    /**
     * @var bool
     */
    protected $isDefault = false;

    /**
     * The filters used for the files and folder names.
     *
     * @var array
     */
    protected $fileAndFolderNameFilters = [];

    /**
     * Levels numbers used to generate hashed subfolders in the processing folder
     */
    const PROCESSING_FOLDER_LEVELS = 2;

    /**
     * Constructor for a storage object.
     *
     * @param Driver\DriverInterface $driver
     * @param array $storageRecord The storage record row from the database
     */
    public function __construct(Driver\DriverInterface $driver, array $storageRecord)
    {
        $this->storageRecord = $storageRecord;
        $this->configuration = $this->getResourceFactoryInstance()->convertFlexFormDataToConfigurationArray($storageRecord['configuration']);
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
            // Configuration error
            $this->isOnline = false;

            $message = sprintf(
                'Failed initializing storage [%d] "%s", error: %s',
                $this->getUid(),
                $this->getName(),
                $e->getMessage()
            );

            $this->getLogger()->error($message);
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
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Sets the configuration.
     *
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Gets the storage record.
     *
     * @return array
     */
    public function getStorageRecord()
    {
        return $this->storageRecord;
    }

    /**
     * Sets the storage that belongs to this storage.
     *
     * @param Driver\DriverInterface $driver
     * @return ResourceStorage
     */
    public function setDriver(Driver\DriverInterface $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Returns the driver object belonging to this storage.
     *
     * @return Driver\DriverInterface
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * Returns the name of this storage.
     *
     * @return string
     */
    public function getName()
    {
        return $this->storageRecord['name'];
    }

    /**
     * Returns the UID of this storage.
     *
     * @return int
     */
    public function getUid()
    {
        return (int)$this->storageRecord['uid'];
    }

    /**
     * Tells whether there are children in this storage.
     *
     * @return bool
     */
    public function hasChildren()
    {
        return true;
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
    public function getCapabilities()
    {
        return (int)$this->capabilities;
    }

    /**
     * Returns TRUE if this storage has the given capability.
     *
     * @param int $capability A capability, as defined in a CAPABILITY_* constant
     * @return bool
     */
    protected function hasCapability($capability)
    {
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
    public function isPublic()
    {
        return $this->hasCapability(self::CAPABILITY_PUBLIC);
    }

    /**
     * Returns TRUE if this storage is writable. This is determined by the
     * driver and the storage configuration; user permissions are not taken into account.
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->hasCapability(self::CAPABILITY_WRITABLE);
    }

    /**
     * Returns TRUE if this storage is browsable by a (backend) user of TYPO3.
     *
     * @return bool
     */
    public function isBrowsable()
    {
        return $this->isOnline() && $this->hasCapability(self::CAPABILITY_BROWSABLE);
    }

    /**
     * Returns TRUE if the identifiers used by this storage are case-sensitive.
     *
     * @return bool
     */
    public function usesCaseSensitiveIdentifiers()
    {
        return $this->driver->isCaseSensitiveFileSystem();
    }

    /**
     * Returns TRUE if this storage is browsable by a (backend) user of TYPO3.
     *
     * @return bool
     */
    public function isOnline()
    {
        if ($this->isOnline === null) {
            if ($this->getUid() === 0) {
                $this->isOnline = true;
            }
            // the storage is not marked as online for a longer time
            if ($this->storageRecord['is_online'] == 0) {
                $this->isOnline = false;
            }
            if ($this->isOnline !== false) {
                // all files are ALWAYS available in the frontend
                if (TYPO3_MODE === 'FE') {
                    $this->isOnline = true;
                } else {
                    // check if the storage is disabled temporary for now
                    $registryObject = GeneralUtility::makeInstance(Registry::class);
                    $offlineUntil = $registryObject->get('core', 'sys_file_storage-' . $this->getUid() . '-offline-until');
                    if ($offlineUntil && $offlineUntil > time()) {
                        $this->isOnline = false;
                    } else {
                        $this->isOnline = true;
                    }
                }
            }
        }
        return $this->isOnline;
    }

    /**
     * Returns TRUE if auto extracting of metadata is enabled
     *
     * @return bool
     */
    public function autoExtractMetadataEnabled()
    {
        return !empty($this->storageRecord['auto_extract_metadata']);
    }

    /**
     * Blows the "fuse" and marks the storage as offline.
     *
     * Can only be modified by an admin.
     *
     * Typically, this is only done if the configuration is wrong.
     */
    public function markAsPermanentlyOffline()
    {
        if ($this->getUid() > 0) {
            // @todo: move this to the storage repository
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_file_storage')
                ->update(
                    'sys_file_storage',
                    ['is_online' => 0],
                    ['uid' => (int)$this->getUid()]
                );
        }
        $this->storageRecord['is_online'] = 0;
        $this->isOnline = false;
    }

    /**
     * Marks this storage as offline for the next 5 minutes.
     *
     * Non-permanent: This typically happens for remote storages
     * that are "flaky" and not available all the time.
     */
    public function markAsTemporaryOffline()
    {
        $registryObject = GeneralUtility::makeInstance(Registry::class);
        $registryObject->set('core', 'sys_file_storage-' . $this->getUid() . '-offline-until', time() + 60 * 5);
        $this->storageRecord['is_online'] = 0;
        $this->isOnline = false;
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
     */
    public function addFileMount($folderIdentifier, $additionalData = [])
    {
        // check for the folder before we add it as a filemount
        if ($this->driver->folderExists($folderIdentifier) === false) {
            // if there is an error, this is important and should be handled
            // as otherwise the user would see the whole storage without any restrictions for the filemounts
            throw new Exception\FolderDoesNotExistException('Folder for file mount ' . $folderIdentifier . ' does not exist.', 1334427099);
        }
        $data = $this->driver->getFolderInfoByIdentifier($folderIdentifier);
        $folderObject = $this->getResourceFactoryInstance()->createFolderObject($this, $data['identifier'], $data['name']);
        // Use the canonical identifier instead of the user provided one!
        $folderIdentifier = $folderObject->getIdentifier();
        if (
            !empty($this->fileMounts[$folderIdentifier])
            && empty($this->fileMounts[$folderIdentifier]['read_only'])
            && !empty($additionalData['read_only'])
        ) {
            // Do not overwrite a regular mount with a read only mount
            return;
        }
        if (empty($additionalData)) {
            $additionalData = [
                'path' => $folderIdentifier,
                'title' => $folderIdentifier,
                'folder' => $folderObject
            ];
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
    public function getFileMounts()
    {
        return $this->fileMounts;
    }

    /**
     * Checks if the given subject is within one of the registered user
     * file mounts. If not, working with the file is not permitted for the user.
     *
     * @param ResourceInterface $subject file or folder
     * @param bool $checkWriteAccess If true, it is not only checked if the subject is within the file mount but also whether it isn't a read only file mount
     * @return bool
     */
    public function isWithinFileMountBoundaries($subject, $checkWriteAccess = false)
    {
        if (!$this->evaluatePermissions) {
            return true;
        }
        $isWithinFileMount = false;
        if (!$subject) {
            $subject = $this->getRootLevelFolder();
        }
        $identifier = $subject->getIdentifier();

        // Allow access to processing folder
        if ($this->isWithinProcessingFolder($identifier)) {
            $isWithinFileMount = true;
        } else {
            // Check if the identifier of the subject is within at
            // least one of the file mounts
            $writableFileMountAvailable = false;
            foreach ($this->fileMounts as $fileMount) {
                /** @var Folder $folder */
                $folder = $fileMount['folder'];
                if ($this->driver->isWithin($folder->getIdentifier(), $identifier)) {
                    $isWithinFileMount = true;
                    if (!$checkWriteAccess) {
                        break;
                    }
                    if (empty($fileMount['read_only'])) {
                        $writableFileMountAvailable = true;
                        break;
                    }
                }
            }
            $isWithinFileMount = $checkWriteAccess ? $writableFileMountAvailable : $isWithinFileMount;
        }
        return $isWithinFileMount;
    }

    /**
     * Sets whether the permissions to access or write
     * into this storage should be checked or not.
     *
     * @param bool $evaluatePermissions
     */
    public function setEvaluatePermissions($evaluatePermissions)
    {
        $this->evaluatePermissions = (bool)$evaluatePermissions;
    }

    /**
     * Gets whether the permissions to access or write
     * into this storage should be checked or not.
     *
     * @return bool $evaluatePermissions
     */
    public function getEvaluatePermissions()
    {
        return $this->evaluatePermissions;
    }

    /**
     * Sets the user permissions of the storage.
     *
     * @param array $userPermissions
     */
    public function setUserPermissions(array $userPermissions)
    {
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
    public function checkUserActionPermission($action, $type)
    {
        if (!$this->evaluatePermissions) {
            return true;
        }

        $allow = false;
        if (!empty($this->userPermissions[strtolower($action) . ucfirst(strtolower($type))])) {
            $allow = true;
        }

        return $allow;
    }

    /**
     * Checks if a file operation (= action) is allowed on a
     * File/Folder/Storage (= subject).
     *
     * This method, by design, does not throw exceptions or do logging.
     * Besides the usage from other methods in this class, it is also used by
     * the Filelist UI to check whether an action is allowed and whether action
     * related UI elements should thus be shown (move icon, edit icon, etc.)
     *
     * @param string $action action, can be read, write, delete
     * @param FileInterface $file
     * @return bool
     */
    public function checkFileActionPermission($action, FileInterface $file)
    {
        $isProcessedFile = $file instanceof ProcessedFile;
        // Check 1: Does the user have permission to perform the action? e.g. "readFile"
        if (!$isProcessedFile && $this->checkUserActionPermission($action, 'File') === false) {
            return false;
        }
        // Check 2: No action allowed on files for denied file extensions
        if (!$this->checkFileExtensionPermission($file->getName())) {
            return false;
        }
        $isReadCheck = false;
        if (in_array($action, ['read', 'copy', 'move', 'replace'], true)) {
            $isReadCheck = true;
        }
        $isWriteCheck = false;
        if (in_array($action, ['add', 'write', 'move', 'rename', 'replace', 'delete'], true)) {
            $isWriteCheck = true;
        }
        // Check 3: Does the user have the right to perform the action?
        // (= is he within the file mount borders)
        if (!$isProcessedFile && !$this->isWithinFileMountBoundaries($file, $isWriteCheck)) {
            return false;
        }

        $isMissing = false;
        if (!$isProcessedFile && $file instanceof File) {
            $isMissing = $file->isMissing();
        }

        if ($this->driver->fileExists($file->getIdentifier()) === false) {
            $file->setMissing(true);
            $isMissing = true;
        }

        // Check 4: Check the capabilities of the storage (and the driver)
        if ($isWriteCheck && ($isMissing || !$this->isWritable())) {
            return false;
        }

        // Check 5: "File permissions" of the driver (only when file isn't marked as missing)
        if (!$isMissing) {
            $filePermissions = $this->driver->getPermissions($file->getIdentifier());
            if ($isReadCheck && !$filePermissions['r']) {
                return false;
            }
            if ($isWriteCheck && !$filePermissions['w']) {
                return false;
            }
        }
        return true;
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
    public function checkFolderActionPermission($action, Folder $folder = null)
    {
        // Check 1: Does the user have permission to perform the action? e.g. "writeFolder"
        if ($this->checkUserActionPermission($action, 'Folder') === false) {
            return false;
        }

        // If we do not have a folder here, we cannot do further checks
        if ($folder === null) {
            return true;
        }

        $isReadCheck = false;
        if (in_array($action, ['read', 'copy'], true)) {
            $isReadCheck = true;
        }
        $isWriteCheck = false;
        if (in_array($action, ['add', 'move', 'write', 'delete', 'rename'], true)) {
            $isWriteCheck = true;
        }
        // Check 2: Does the user has the right to perform the action?
        // (= is he within the file mount borders)
        if (!$this->isWithinFileMountBoundaries($folder, $isWriteCheck)) {
            return false;
        }
        // Check 3: Check the capabilities of the storage (and the driver)
        if ($isReadCheck && !$this->isBrowsable()) {
            return false;
        }
        if ($isWriteCheck && !$this->isWritable()) {
            return false;
        }

        // Check 4: "Folder permissions" of the driver
        $folderPermissions = $this->driver->getPermissions($folder->getIdentifier());
        if ($isReadCheck && !$folderPermissions['r']) {
            return false;
        }
        if ($isWriteCheck && !$folderPermissions['w']) {
            return false;
        }
        return true;
    }

    /**
     * @param ResourceInterface $fileOrFolder
     * @return bool
     */
    public function checkFileAndFolderNameFilters(ResourceInterface $fileOrFolder)
    {
        foreach ($this->fileAndFolderNameFilters as $filter) {
            if (is_callable($filter)) {
                $result = call_user_func($filter, $fileOrFolder->getName(), $fileOrFolder->getIdentifier(), $fileOrFolder->getParentFolder()->getIdentifier(), [], $this->driver);
                // We have to use -1 as the „don't include“ return value, as call_user_func() will return FALSE
                // If calling the method succeeded and thus we can't use that as a return value.
                if ($result === -1) {
                    return false;
                }
                if ($result === false) {
                    throw new \RuntimeException(
                        'Could not apply file/folder name filter ' . $filter[0] . '::' . $filter[1],
                        1525342106
                    );
                }
            }
        }

        return true;
    }

    /**
     * If the fileName is given, checks it against the
     * TYPO3_CONF_VARS[BE][fileDenyPattern] + and if the file extension is allowed.
     *
     * @param string $fileName full filename
     * @return bool TRUE if extension/filename is allowed
     */
    protected function checkFileExtensionPermission($fileName)
    {
        $fileName = $this->driver->sanitizeFileName($fileName);
        $isAllowed = GeneralUtility::verifyFilenameAgainstDenyPattern($fileName);
        if ($isAllowed && $this->evaluatePermissions) {
            $fileExtension = strtolower(PathUtility::pathinfo($fileName, PATHINFO_EXTENSION));
            // Set up the permissions for the file extension
            $fileExtensionPermissions = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace'];
            $fileExtensionPermissions['allow'] = GeneralUtility::uniqueList(strtolower($fileExtensionPermissions['allow']));
            $fileExtensionPermissions['deny'] = GeneralUtility::uniqueList(strtolower($fileExtensionPermissions['deny']));
            if ($fileExtension !== '') {
                // If the extension is found amongst the allowed types, we return TRUE immediately
                if ($fileExtensionPermissions['allow'] === '*' || GeneralUtility::inList($fileExtensionPermissions['allow'], $fileExtension)) {
                    return true;
                }
                // If the extension is found amongst the denied types, we return FALSE immediately
                if ($fileExtensionPermissions['deny'] === '*' || GeneralUtility::inList($fileExtensionPermissions['deny'], $fileExtension)) {
                    return false;
                }
                // If no match we return TRUE
                return true;
            }
            if ($fileExtensionPermissions['allow'] === '*') {
                return true;
            }
            if ($fileExtensionPermissions['deny'] === '*') {
                return false;
            }
            return true;
        }
        return $isAllowed;
    }

    /**
     * Assures read permission for given folder.
     *
     * @param Folder $folder If a folder is given, mountpoints are checked. If not only user folder read permissions are checked.
     * @throws Exception\InsufficientFolderAccessPermissionsException
     */
    protected function assureFolderReadPermission(Folder $folder = null)
    {
        if (!$this->checkFolderActionPermission('read', $folder)) {
            if ($folder === null) {
                throw new Exception\InsufficientFolderAccessPermissionsException(
                    'You are not allowed to read folders',
                    1430657869
                );
            }
            throw new Exception\InsufficientFolderAccessPermissionsException(
                    'You are not allowed to access the given folder: "' . $folder->getName() . '"',
                    1375955684
                );
        }
    }

    /**
     * Assures delete permission for given folder.
     *
     * @param Folder $folder If a folder is given, mountpoints are checked. If not only user folder delete permissions are checked.
     * @param bool $checkDeleteRecursively
     * @throws Exception\InsufficientFolderAccessPermissionsException
     * @throws Exception\InsufficientFolderWritePermissionsException
     * @throws Exception\InsufficientUserPermissionsException
     */
    protected function assureFolderDeletePermission(Folder $folder, $checkDeleteRecursively)
    {
        // Check user permissions for recursive deletion if it is requested
        if ($checkDeleteRecursively && !$this->checkUserActionPermission('recursivedelete', 'Folder')) {
            throw new Exception\InsufficientUserPermissionsException('You are not allowed to delete folders recursively', 1377779423);
        }
        // Check user action permission
        if (!$this->checkFolderActionPermission('delete', $folder)) {
            throw new Exception\InsufficientFolderAccessPermissionsException(
                'You are not allowed to delete the given folder: "' . $folder->getName() . '"',
                1377779039
            );
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
     * @throws Exception\InsufficientFileAccessPermissionsException
     * @throws Exception\IllegalFileExtensionException
     */
    protected function assureFileReadPermission(FileInterface $file)
    {
        if (!$this->checkFileActionPermission('read', $file)) {
            throw new Exception\InsufficientFileAccessPermissionsException(
                'You are not allowed to access that file: "' . $file->getName() . '"',
                1375955429
            );
        }
        if (!$this->checkFileExtensionPermission($file->getName())) {
            throw new Exception\IllegalFileExtensionException(
                'You are not allowed to use that file extension. File: "' . $file->getName() . '"',
                1375955430
            );
        }
    }

    /**
     * Assures write permission for given file.
     *
     * @param FileInterface $file
     * @throws Exception\IllegalFileExtensionException
     * @throws Exception\InsufficientFileWritePermissionsException
     * @throws Exception\InsufficientUserPermissionsException
     */
    protected function assureFileWritePermissions(FileInterface $file)
    {
        // Check if user is allowed to write the file and $file is writable
        if (!$this->checkFileActionPermission('write', $file)) {
            throw new Exception\InsufficientFileWritePermissionsException('Writing to file "' . $file->getIdentifier() . '" is not allowed.', 1330121088);
        }
        if (!$this->checkFileExtensionPermission($file->getName())) {
            throw new Exception\IllegalFileExtensionException('You are not allowed to edit a file with extension "' . $file->getExtension() . '"', 1366711933);
        }
    }

    /**
     * Assure replace permission for given file.
     *
     * @param FileInterface $file
     * @throws Exception\InsufficientFileWritePermissionsException
     * @throws Exception\InsufficientFolderWritePermissionsException
     */
    protected function assureFileReplacePermissions(FileInterface $file)
    {
        // Check if user is allowed to replace the file and $file is writable
        if (!$this->checkFileActionPermission('replace', $file)) {
            throw new Exception\InsufficientFileWritePermissionsException('Replacing file "' . $file->getIdentifier() . '" is not allowed.', 1436899571);
        }
        // Check if parentFolder is writable for the user
        if (!$this->checkFolderActionPermission('write', $file->getParentFolder())) {
            throw new Exception\InsufficientFolderWritePermissionsException('You are not allowed to write to the target folder "' . $file->getIdentifier() . '"', 1436899572);
        }
    }

    /**
     * Assures delete permission for given file.
     *
     * @param FileInterface $file
     * @throws Exception\IllegalFileExtensionException
     * @throws Exception\InsufficientFileWritePermissionsException
     * @throws Exception\InsufficientFolderWritePermissionsException
     */
    protected function assureFileDeletePermissions(FileInterface $file)
    {
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
     * Checks if a file/user has the permission to be written to a Folder/Storage.
     * If not, throws an exception.
     *
     * @param Folder $targetFolder The target folder where the file should be written
     * @param string $targetFileName The file name which should be written into the storage
     *
     * @throws Exception\InsufficientFolderWritePermissionsException
     * @throws Exception\IllegalFileExtensionException
     * @throws Exception\InsufficientUserPermissionsException
     */
    protected function assureFileAddPermissions($targetFolder, $targetFileName)
    {
        // Check for a valid file extension
        if (!$this->checkFileExtensionPermission($targetFileName)) {
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
     * @param Folder $targetFolder The target folder where the file should be uploaded
     * @param string $targetFileName the destination file name $_FILES['file1']['name']
     * @param int $uploadedFileSize
     *
     * @throws Exception\InsufficientFolderWritePermissionsException
     * @throws Exception\UploadException
     * @throws Exception\IllegalFileExtensionException
     * @throws Exception\UploadSizeException
     * @throws Exception\InsufficientUserPermissionsException
     */
    protected function assureFileUploadPermissions($localFilePath, $targetFolder, $targetFileName, $uploadedFileSize)
    {
        // Makes sure this is an uploaded file
        if (!is_uploaded_file($localFilePath)) {
            throw new Exception\UploadException('The upload has failed, no uploaded file found!', 1322110455);
        }
        // Max upload size (kb) for files.
        $maxUploadFileSize = GeneralUtility::getMaxUploadFileSize() * 1024;
        if ($maxUploadFileSize > 0 && $uploadedFileSize >= $maxUploadFileSize) {
            unlink($localFilePath);
            throw new Exception\UploadSizeException('The uploaded file exceeds the size-limit of ' . $maxUploadFileSize . ' bytes', 1322110041);
        }
        $this->assureFileAddPermissions($targetFolder, $targetFileName);
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
     */
    protected function assureFileMovePermissions(FileInterface $file, Folder $targetFolder, $targetFileName)
    {
        // Check if targetFolder is within this storage
        if ($this->getUid() !== $targetFolder->getStorage()->getUid()) {
            throw new \RuntimeException('The target folder is not in the same storage. Target folder given: "' . $targetFolder->getIdentifier() . '"', 1422553107);
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
     */
    protected function assureFileRenamePermissions(FileInterface $file, $targetFileName)
    {
        // Check if file extension is allowed
        if (!$this->checkFileExtensionPermission($targetFileName) || !$this->checkFileExtensionPermission($file->getName())) {
            throw new Exception\IllegalFileExtensionException('You are not allowed to rename a file with this extension. File given: "' . $file->getName() . '"', 1371466663);
        }
        // Check if user is allowed to rename
        if (!$this->checkFileActionPermission('rename', $file)) {
            throw new Exception\InsufficientUserPermissionsException('You are not allowed to rename files. File given: "' . $file->getName() . '"', 1319219351);
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
     */
    protected function assureFileCopyPermissions(FileInterface $file, Folder $targetFolder, $targetFileName)
    {
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
     *
     * @throws Exception
     * @throws Exception\InsufficientFolderWritePermissionsException
     * @throws Exception\IllegalFileExtensionException
     * @throws Exception\InsufficientFileReadPermissionsException
     * @throws Exception\InsufficientUserPermissionsException
     * @throws \RuntimeException
     */
    protected function assureFolderCopyPermissions(FolderInterface $folderToCopy, FolderInterface $targetParentFolder)
    {
        // Check if targetFolder is within this storage, this should never happen
        if ($this->getUid() !== $targetParentFolder->getStorage()->getUid()) {
            throw new Exception('The operation of the folder cannot be called by this storage "' . $this->getUid() . '"', 1377777624);
        }
        if (!$folderToCopy instanceof Folder) {
            throw new \RuntimeException('The folder "' . $folderToCopy->getIdentifier() . '" to copy is not of type folder.', 1384209020);
        }
        // Check if user is allowed to copy and the folder is readable
        if (!$folderToCopy->getStorage()->checkFolderActionPermission('copy', $folderToCopy)) {
            throw new Exception\InsufficientFileReadPermissionsException('You are not allowed to copy the folder "' . $folderToCopy->getIdentifier() . '"', 1377777629);
        }
        if (!$targetParentFolder instanceof Folder) {
            throw new \RuntimeException('The target folder "' . $targetParentFolder->getIdentifier() . '" is not of type folder.', 1384209021);
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
     */
    protected function assureFolderMovePermissions(FolderInterface $folderToMove, FolderInterface $targetParentFolder)
    {
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

    /**
     * Clean a fileName from not allowed characters
     *
     * @param string $fileName The name of the file to be add, If not set, the local file name is used
     * @param Folder $targetFolder The target folder where the file should be added
     *
     * @throws \InvalidArgumentException
     * @throws Exception\ExistingTargetFileNameException
     * @return FileInterface
     */
    public function sanitizeFileName($fileName, Folder $targetFolder = null)
    {
        $targetFolder = $targetFolder ?: $this->getDefaultFolder();
        $fileName = $this->driver->sanitizeFileName($fileName);

        // The file name could be changed by an external slot
        $fileName = $this->emitSanitizeFileNameSignal($fileName, $targetFolder);

        return $fileName;
    }

    /********************
     * FILE ACTIONS
     ********************/
    /**
     * Moves a file from the local filesystem to this storage.
     *
     * @param string $localFilePath The file on the server's hard disk to add
     * @param Folder $targetFolder The target folder where the file should be added
     * @param string $targetFileName The name of the file to be add, If not set, the local file name is used
     * @param string $conflictMode a value of the DuplicationBehavior enumeration
     * @param bool $removeOriginal if set the original file will be removed after successful operation
     *
     * @throws \InvalidArgumentException
     * @throws Exception\ExistingTargetFileNameException
     * @return FileInterface
     */
    public function addFile($localFilePath, Folder $targetFolder, $targetFileName = '', $conflictMode = DuplicationBehavior::RENAME, $removeOriginal = true)
    {
        $localFilePath = PathUtility::getCanonicalPath($localFilePath);
        // File is not available locally NOR is it an uploaded file
        if (!is_uploaded_file($localFilePath) && !file_exists($localFilePath)) {
            throw new \InvalidArgumentException('File "' . $localFilePath . '" does not exist.', 1319552745);
        }
        $conflictMode = DuplicationBehavior::cast($conflictMode);
        $targetFolder = $targetFolder ?: $this->getDefaultFolder();
        $targetFileName = $this->sanitizeFileName($targetFileName ?: PathUtility::basename($localFilePath), $targetFolder);

        $targetFileName = $this->emitPreFileAddSignal($targetFileName, $targetFolder, $localFilePath);

        $this->assureFileAddPermissions($targetFolder, $targetFileName);

        $replaceExisting = false;
        if ($conflictMode->equals(DuplicationBehavior::CANCEL) && $this->driver->fileExistsInFolder($targetFileName, $targetFolder->getIdentifier())) {
            throw new Exception\ExistingTargetFileNameException('File "' . $targetFileName . '" already exists in folder ' . $targetFolder->getIdentifier(), 1322121068);
        }
        if ($conflictMode->equals(DuplicationBehavior::RENAME)) {
            $targetFileName = $this->getUniqueName($targetFolder, $targetFileName);
        } elseif ($conflictMode->equals(DuplicationBehavior::REPLACE) && $this->driver->fileExistsInFolder($targetFileName, $targetFolder->getIdentifier())) {
            $replaceExisting = true;
        }

        $fileIdentifier = $this->driver->addFile($localFilePath, $targetFolder->getIdentifier(), $targetFileName, $removeOriginal);
        $file = $this->getResourceFactoryInstance()->getFileObjectByStorageAndIdentifier($this->getUid(), $fileIdentifier);

        if ($replaceExisting && $file instanceof File) {
            $this->getIndexer()->updateIndexEntry($file);
        }
        if ($this->autoExtractMetadataEnabled()) {
            $this->getIndexer()->extractMetaData($file);
        }

        $this->emitPostFileAddSignal($file, $targetFolder);

        return $file;
    }

    /**
     * Updates a processed file with a new file from the local filesystem.
     *
     * @param string $localFilePath
     * @param ProcessedFile $processedFile
     * @param Folder $processingFolder
     * @return FileInterface
     * @throws \InvalidArgumentException
     * @internal use only
     */
    public function updateProcessedFile($localFilePath, ProcessedFile $processedFile, Folder $processingFolder = null)
    {
        if (!file_exists($localFilePath)) {
            throw new \InvalidArgumentException('File "' . $localFilePath . '" does not exist.', 1319552746);
        }
        if ($processingFolder === null) {
            $processingFolder = $this->getProcessingFolder($processedFile->getOriginalFile());
        }
        $fileIdentifier = $this->driver->addFile($localFilePath, $processingFolder->getIdentifier(), $processedFile->getName());
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
    public function hashFile(FileInterface $fileObject, $hash)
    {
        return $this->hashFileByIdentifier($fileObject->getIdentifier(), $hash);
    }

    /**
     * Creates a (cryptographic) hash for a fileIdentifier.

     * @param string $fileIdentifier
     * @param string $hash
     *
     * @return string
     */
    public function hashFileByIdentifier($fileIdentifier, $hash)
    {
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
    public function hashFileIdentifier($file)
    {
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
     * @param bool $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
     * @return string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl(ResourceInterface $resourceObject, $relativeToCurrentScript = false)
    {
        $publicUrl = null;
        if ($this->isOnline()) {
            // Pre-process the public URL by an accordant slot
            $this->emitPreGeneratePublicUrlSignal($resourceObject, $relativeToCurrentScript, ['publicUrl' => &$publicUrl]);

            if (
                $publicUrl === null
                && $resourceObject instanceof File
                && ($helper = OnlineMediaHelperRegistry::getInstance()->getOnlineMediaHelper($resourceObject)) !== false
            ) {
                $publicUrl = $helper->getPublicUrl($resourceObject, $relativeToCurrentScript);
            }

            // If slot did not handle the signal, use the default way to determine public URL
            if ($publicUrl === null) {
                if ($this->hasCapability(self::CAPABILITY_PUBLIC)) {
                    $publicUrl = $this->driver->getPublicUrl($resourceObject->getIdentifier());
                }

                if ($publicUrl === null && $resourceObject instanceof FileInterface) {
                    $queryParameterArray = ['eID' => 'dumpFile', 't' => ''];
                    if ($resourceObject instanceof File) {
                        $queryParameterArray['f'] = $resourceObject->getUid();
                        $queryParameterArray['t'] = 'f';
                    } elseif ($resourceObject instanceof ProcessedFile) {
                        $queryParameterArray['p'] = $resourceObject->getUid();
                        $queryParameterArray['t'] = 'p';
                    }

                    $queryParameterArray['token'] = GeneralUtility::hmac(implode('|', $queryParameterArray), 'resourceStorageDumpFile');
                    $publicUrl = GeneralUtility::locationHeaderUrl(PathUtility::getAbsoluteWebPath(PATH_site . 'index.php'));
                    $publicUrl .= '?' . http_build_query($queryParameterArray, '', '&', PHP_QUERY_RFC3986);
                }

                // If requested, make the path relative to the current script in order to make it possible
                // to use the relative file
                if ($publicUrl !== null && $relativeToCurrentScript && !GeneralUtility::isValidUrl($publicUrl)) {
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
    public function processFile(FileInterface $fileObject, $context, array $configuration)
    {
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
    public function getFileForLocalProcessing(FileInterface $fileObject, $writable = true)
    {
        $filePath = $this->driver->getFileForLocalProcessing($fileObject->getIdentifier(), $writable);
        return $filePath;
    }

    /**
     * Gets a file by identifier.
     *
     * @param string $identifier
     * @return FileInterface
     */
    public function getFile($identifier)
    {
        $file = $this->getFileFactory()->getFileObjectByStorageAndIdentifier($this->getUid(), $identifier);
        if (!$this->driver->fileExists($identifier)) {
            $file->setMissing(true);
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
    public function getFileInfo(FileInterface $fileObject)
    {
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
    public function getFileInfoByIdentifier($identifier, array $propertiesToExtract = [])
    {
        return $this->driver->getFileInfoByIdentifier($identifier, $propertiesToExtract);
    }

    /**
     * Unsets the file and folder name filters, thus making this storage return unfiltered filelists.
     */
    public function unsetFileAndFolderNameFilters()
    {
        $this->fileAndFolderNameFilters = [];
    }

    /**
     * Resets the file and folder name filters to the default values defined in the TYPO3 configuration.
     */
    public function resetFileAndFolderNameFiltersToDefault()
    {
        $this->fileAndFolderNameFilters = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['defaultFilterCallbacks'];
    }

    /**
     * Returns the file and folder name filters used by this storage.
     *
     * @return array
     */
    public function getFileAndFolderNameFilters()
    {
        return $this->fileAndFolderNameFilters;
    }

    /**
     * @param array $filters
     * @return $this
     */
    public function setFileAndFolderNameFilters(array $filters)
    {
        $this->fileAndFolderNameFilters = $filters;
        return $this;
    }

    /**
     * @param callable $filter
     */
    public function addFileAndFolderNameFilter($filter)
    {
        $this->fileAndFolderNameFilters[] = $filter;
    }

    /**
     * @param string $fileIdentifier
     *
     * @return string
     */
    public function getFolderIdentifierFromFileIdentifier($fileIdentifier)
    {
        return $this->driver->getParentFolderIdentifierOfIdentifier($fileIdentifier);
    }

    /**
     * Get file from folder
     *
     * @param string $fileName
     * @param Folder $folder
     * @return File|ProcessedFile|null
     */
    public function getFileInFolder($fileName, Folder $folder)
    {
        $identifier = $this->driver->getFileInFolder($fileName, $folder->getIdentifier());
        return $this->getFileFactory()->getFileObjectByStorageAndIdentifier($this->getUid(), $identifier);
    }

    /**
     * @param Folder $folder
     * @param int $start
     * @param int $maxNumberOfItems
     * @param bool $useFilters
     * @param bool $recursive
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return File[]
     * @throws Exception\InsufficientFolderAccessPermissionsException
     */
    public function getFilesInFolder(Folder $folder, $start = 0, $maxNumberOfItems = 0, $useFilters = true, $recursive = false, $sort = '', $sortRev = false)
    {
        $this->assureFolderReadPermission($folder);

        $rows = $this->getFileIndexRepository()->findByFolder($folder);

        $filters = $useFilters == true ? $this->fileAndFolderNameFilters : [];
        $fileIdentifiers = array_values($this->driver->getFilesInFolder($folder->getIdentifier(), $start, $maxNumberOfItems, $recursive, $filters, $sort, $sortRev));

        $items = [];
        foreach ($fileIdentifiers as $identifier) {
            if (isset($rows[$identifier])) {
                $fileObject = $this->getFileFactory()->getFileObject($rows[$identifier]['uid'], $rows[$identifier]);
            } else {
                $fileObject = $this->getFileFactory()->getFileObjectByStorageAndIdentifier($this->getUid(), $identifier);
            }
            if ($fileObject instanceof FileInterface) {
                $key = $fileObject->getName();
                while (isset($items[$key])) {
                    $key .= 'z';
                }
                $items[$key] = $fileObject;
            }
        }

        return $items;
    }

    /**
     * @param string $folderIdentifier
     * @param bool $useFilters
     * @param bool $recursive
     * @return array
     */
    public function getFileIdentifiersInFolder($folderIdentifier, $useFilters = true, $recursive = false)
    {
        $filters = $useFilters == true ? $this->fileAndFolderNameFilters : [];
        return $this->driver->getFilesInFolder($folderIdentifier, 0, 0, $recursive, $filters);
    }

    /**
     * @param Folder $folder
     * @param bool $useFilters
     * @param bool $recursive
     * @return int Number of files in folder
     * @throws Exception\InsufficientFolderAccessPermissionsException
     */
    public function countFilesInFolder(Folder $folder, $useFilters = true, $recursive = false)
    {
        $this->assureFolderReadPermission($folder);
        $filters = $useFilters ? $this->fileAndFolderNameFilters : [];
        return $this->driver->countFilesInFolder($folder->getIdentifier(), $recursive, $filters);
    }

    /**
     * @param string $folderIdentifier
     * @param bool $useFilters
     * @param bool $recursive
     * @return array
     */
    public function getFolderIdentifiersInFolder($folderIdentifier, $useFilters = true, $recursive = false)
    {
        $filters = $useFilters == true ? $this->fileAndFolderNameFilters : [];
        return $this->driver->getFoldersInFolder($folderIdentifier, 0, 0, $recursive, $filters);
    }

    /**
     * Returns TRUE if the specified file exists
     *
     * @param string $identifier
     * @return bool
     */
    public function hasFile($identifier)
    {
        // Allow if identifier is in processing folder
        if (!$this->isWithinProcessingFolder($identifier)) {
            $this->assureFolderReadPermission();
        }
        return $this->driver->fileExists($identifier);
    }

    /**
     * Get all processing folders that live in this storage
     *
     * @return Folder[]
     */
    public function getProcessingFolders()
    {
        if ($this->processingFolders === null) {
            $this->processingFolders = [];
            $this->processingFolders[] = $this->getProcessingFolder();
            /** @var $storageRepository StorageRepository */
            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            $allStorages = $storageRepository->findAll();
            foreach ($allStorages as $storage) {
                // To circumvent the permission check of the folder, we use the factory to create it "manually" instead of directly using $storage->getProcessingFolder()
                // See #66695 for details
                list($storageUid, $processingFolderIdentifier) = GeneralUtility::trimExplode(':', $storage->getStorageRecord()['processingfolder']);
                if (empty($processingFolderIdentifier) || (int)$storageUid !== $this->getUid()) {
                    continue;
                }
                $potentialProcessingFolder = $this->getResourceFactoryInstance()->getInstance()->createFolderObject($this, $processingFolderIdentifier, $processingFolderIdentifier);
                if ($potentialProcessingFolder->getStorage() === $this && $potentialProcessingFolder->getIdentifier() !== $this->getProcessingFolder()->getIdentifier()) {
                    $this->processingFolders[] = $potentialProcessingFolder;
                }
            }
        }

        return $this->processingFolders;
    }

    /**
     * Returns TRUE if folder that is in current storage  is set as
     * processing folder for one of the existing storages
     *
     * @param Folder $folder
     * @return bool
     */
    public function isProcessingFolder(Folder $folder)
    {
        $isProcessingFolder = false;
        foreach ($this->getProcessingFolders() as $processingFolder) {
            if ($folder->getCombinedIdentifier() === $processingFolder->getCombinedIdentifier()) {
                $isProcessingFolder = true;
                break;
            }
        }
        return $isProcessingFolder;
    }

    /**
     * Checks if the queried file in the given folder exists
     *
     * @param string $fileName
     * @param Folder $folder
     * @return bool
     */
    public function hasFileInFolder($fileName, Folder $folder)
    {
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
    public function getFileContents($file)
    {
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
     * @param string $overrideMimeType If set this will be used as Content-Type header instead of the automatically detected mime type.
     */
    public function dumpFileContents(FileInterface $file, $asDownload = false, $alternativeFilename = null, $overrideMimeType = null)
    {
        $downloadName = $alternativeFilename ?: $file->getName();
        $contentDisposition = $asDownload ? 'attachment' : 'inline';
        header('Content-Disposition: ' . $contentDisposition . '; filename="' . $downloadName . '"');
        header('Content-Type: ' . ($overrideMimeType ?: $file->getMimeType()));
        header('Content-Length: ' . $file->getSize());

        // Cache-Control header is needed here to solve an issue with browser IE8 and lower
        // See for more information: http://support.microsoft.com/kb/323308
        header("Cache-Control: ''");
        header(
            'Last-Modified: ' .
            gmdate('D, d M Y H:i:s', array_pop($this->driver->getFileInfoByIdentifier($file->getIdentifier(), ['mtime']))) . ' GMT',
            true,
            200
        );
        ob_clean();
        flush();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
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
    public function setFileContents(AbstractFile $file, $contents)
    {
        // Check if user is allowed to edit
        $this->assureFileWritePermissions($file);
        // Call driver method to update the file and update file index entry afterwards
        $result = $this->driver->setFileContents($file->getIdentifier(), $contents);
        if ($file instanceof File) {
            $this->getIndexer()->updateIndexEntry($file);
        }
        $this->emitPostFileSetContentsSignal($file, $contents);
        return $result;
    }

    /**
     * Creates a new file
     *
     * previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::func_newfile()
     *
     * @param string $fileName The name of the file to be created
     * @param Folder $targetFolderObject The target folder where the file should be created
     *
     * @throws Exception\IllegalFileExtensionException
     * @throws Exception\InsufficientFolderWritePermissionsException
     * @return FileInterface The file object
     */
    public function createFile($fileName, Folder $targetFolderObject)
    {
        $this->assureFileAddPermissions($targetFolderObject, $fileName);
        $newFileIdentifier = $this->driver->createFile($fileName, $targetFolderObject->getIdentifier());
        $this->emitPostFileCreateSignal($newFileIdentifier, $targetFolderObject);
        return $this->getResourceFactoryInstance()->getFileObjectByStorageAndIdentifier($this->getUid(), $newFileIdentifier);
    }

    /**
     * Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::deleteFile()
     *
     * @param FileInterface $fileObject
     * @throws Exception\InsufficientFileAccessPermissionsException
     * @throws Exception\FileOperationErrorException
     * @return bool TRUE if deletion succeeded
     */
    public function deleteFile($fileObject)
    {
        $this->assureFileDeletePermissions($fileObject);

        $this->emitPreFileDeleteSignal($fileObject);
        $deleted = true;

        if ($this->driver->fileExists($fileObject->getIdentifier())) {
            // Disable permission check to find nearest recycler and move file without errors
            $currentPermissions = $this->evaluatePermissions;
            $this->evaluatePermissions = false;

            $recyclerFolder = $this->getNearestRecyclerFolder($fileObject);
            if ($recyclerFolder === null) {
                $result = $this->driver->deleteFile($fileObject->getIdentifier());
            } else {
                $result = $this->moveFile($fileObject, $recyclerFolder);
                $deleted = false;
            }

            $this->evaluatePermissions = $currentPermissions;

            if (!$result) {
                throw new Exception\FileOperationErrorException('Deleting the file "' . $fileObject->getIdentifier() . '\' failed.', 1329831691);
            }
        }
        // Mark the file object as deleted
        if ($deleted && $fileObject instanceof AbstractFile) {
            $fileObject->setDeleted();
        }

        $this->emitPostFileDeleteSignal($fileObject);

        return true;
    }

    /**
     * Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::func_copy()
     * copies a source file (from any location) in to the target
     * folder, the latter has to be part of this storage
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     * @param string $targetFileName an optional destination fileName
     * @param string $conflictMode a value of the DuplicationBehavior enumeration
     *
     * @throws \Exception|Exception\AbstractFileOperationException
     * @throws Exception\ExistingTargetFileNameException
     * @return FileInterface
     */
    public function copyFile(FileInterface $file, Folder $targetFolder, $targetFileName = null, $conflictMode = DuplicationBehavior::RENAME)
    {
        $conflictMode = DuplicationBehavior::cast($conflictMode);
        if ($targetFileName === null) {
            $targetFileName = $file->getName();
        }
        $sanitizedTargetFileName = $this->driver->sanitizeFileName($targetFileName);
        $this->assureFileCopyPermissions($file, $targetFolder, $sanitizedTargetFileName);
        $this->emitPreFileCopySignal($file, $targetFolder);
        // File exists and we should abort, let's abort
        if ($conflictMode->equals(DuplicationBehavior::CANCEL) && $targetFolder->hasFile($sanitizedTargetFileName)) {
            throw new Exception\ExistingTargetFileNameException('The target file already exists.', 1320291064);
        }
        // File exists and we should find another name, let's find another one
        if ($conflictMode->equals(DuplicationBehavior::RENAME) && $targetFolder->hasFile($sanitizedTargetFileName)) {
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
        $newFileObject = $this->getResourceFactoryInstance()->getFileObjectByStorageAndIdentifier($this->getUid(), $newFileObjectIdentifier);
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
     * @param string $conflictMode a value of the DuplicationBehavior enumeration
     *
     * @throws Exception\ExistingTargetFileNameException
     * @throws \RuntimeException
     * @return FileInterface
     */
    public function moveFile($file, $targetFolder, $targetFileName = null, $conflictMode = DuplicationBehavior::RENAME)
    {
        $conflictMode = DuplicationBehavior::cast($conflictMode);
        if ($targetFileName === null) {
            $targetFileName = $file->getName();
        }
        $originalFolder = $file->getParentFolder();
        $sanitizedTargetFileName = $this->driver->sanitizeFileName($targetFileName);
        $this->assureFileMovePermissions($file, $targetFolder, $sanitizedTargetFileName);
        if ($targetFolder->hasFile($sanitizedTargetFileName)) {
            // File exists and we should abort, let's abort
            if ($conflictMode->equals(DuplicationBehavior::RENAME)) {
                $sanitizedTargetFileName = $this->getUniqueName($targetFolder, $sanitizedTargetFileName);
            } elseif ($conflictMode->equals(DuplicationBehavior::CANCEL)) {
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
                $file->updateProperties(['identifier' => $newIdentifier]);
            } else {
                $tempPath = $file->getForLocalProcessing();
                $newIdentifier = $this->driver->addFile($tempPath, $targetFolder->getIdentifier(), $sanitizedTargetFileName);

                // Disable permission check to find nearest recycler and move file without errors
                $currentPermissions = $sourceStorage->evaluatePermissions;
                $sourceStorage->evaluatePermissions = false;

                $recyclerFolder = $sourceStorage->getNearestRecyclerFolder($file);
                if ($recyclerFolder === null) {
                    $sourceStorage->driver->deleteFile($file->getIdentifier());
                } else {
                    $sourceStorage->moveFile($file, $recyclerFolder);
                }
                $sourceStorage->evaluatePermissions = $currentPermissions;
                if ($file instanceof File) {
                    $file->updateProperties(['storage' => $this->getUid(), 'identifier' => $newIdentifier]);
                }
            }
            $this->getIndexer()->updateIndexEntry($file);
        } catch (\TYPO3\CMS\Core\Exception $e) {
            echo $e->getMessage();
        }
        $this->emitPostFileMoveSignal($file, $targetFolder, $originalFolder);
        return $file;
    }

    /**
     * Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::func_rename()
     *
     * @param FileInterface $file
     * @param string $targetFileName
     * @param string $conflictMode
     * @return FileInterface
     * @throws ExistingTargetFileNameException
     */
    public function renameFile($file, $targetFileName, $conflictMode = DuplicationBehavior::RENAME)
    {
        // The name should be different from the current.
        if ($file->getName() === $targetFileName) {
            return $file;
        }
        $sanitizedTargetFileName = $this->driver->sanitizeFileName($targetFileName);
        $this->assureFileRenamePermissions($file, $sanitizedTargetFileName);
        $this->emitPreFileRenameSignal($file, $sanitizedTargetFileName);

        $conflictMode = DuplicationBehavior::cast($conflictMode);

        // Call driver method to rename the file and update the index entry
        try {
            $newIdentifier = $this->driver->renameFile($file->getIdentifier(), $sanitizedTargetFileName);
            if ($file instanceof File) {
                $file->updateProperties(['identifier' => $newIdentifier]);
            }
            $this->getIndexer()->updateIndexEntry($file);
        } catch (ExistingTargetFileNameException $exception) {
            if ($conflictMode->equals(DuplicationBehavior::RENAME)) {
                $newName = $this->getUniqueName($file->getParentFolder(), $sanitizedTargetFileName);
                $file = $this->renameFile($file, $newName);
            } elseif ($conflictMode->equals(DuplicationBehavior::CANCEL)) {
                throw $exception;
            } elseif ($conflictMode->equals(DuplicationBehavior::REPLACE)) {
                $sourceFileIdentifier = substr($file->getCombinedIdentifier(), 0, strrpos($file->getCombinedIdentifier(), '/') + 1) . $targetFileName;
                $sourceFile = $this->getResourceFactoryInstance()->getFileObjectFromCombinedIdentifier($sourceFileIdentifier);
                $file = $this->replaceFile($sourceFile, PATH_site . $file->getPublicUrl());
            }
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
    public function replaceFile(FileInterface $file, $localFilePath)
    {
        $this->assureFileReplacePermissions($file);
        if (!file_exists($localFilePath)) {
            throw new \InvalidArgumentException('File "' . $localFilePath . '" does not exist.', 1325842622);
        }
        $this->emitPreFileReplaceSignal($file, $localFilePath);
        $this->driver->replaceFile($file->getIdentifier(), $localFilePath);
        if ($file instanceof File) {
            $this->getIndexer()->updateIndexEntry($file);
        }
        if ($this->autoExtractMetadataEnabled()) {
            $this->getIndexer()->extractMetaData($file);
        }
        $this->emitPostFileReplaceSignal($file, $localFilePath);

        return $file;
    }

    /**
     * Adds an uploaded file into the Storage. Previously in \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::file_upload()
     *
     * @param array $uploadedFileData contains information about the uploaded file given by $_FILES['file1']
     * @param Folder $targetFolder the target folder
     * @param string $targetFileName the file name to be written
     * @param string $conflictMode a value of the DuplicationBehavior enumeration
     * @return FileInterface The file object
     */
    public function addUploadedFile(array $uploadedFileData, Folder $targetFolder = null, $targetFileName = null, $conflictMode = DuplicationBehavior::CANCEL)
    {
        $conflictMode = DuplicationBehavior::cast($conflictMode);
        $localFilePath = $uploadedFileData['tmp_name'];
        if ($targetFolder === null) {
            $targetFolder = $this->getDefaultFolder();
        }
        if ($targetFileName === null) {
            $targetFileName = $uploadedFileData['name'];
        }
        $targetFileName = $this->driver->sanitizeFileName($targetFileName);

        $this->assureFileUploadPermissions($localFilePath, $targetFolder, $targetFileName, $uploadedFileData['size']);
        if ($this->hasFileInFolder($targetFileName, $targetFolder) && $conflictMode->equals(DuplicationBehavior::REPLACE)) {
            $file = $this->getFileInFolder($targetFileName, $targetFolder);
            $resultObject = $this->replaceFile($file, $localFilePath);
        } else {
            $resultObject = $this->addFile($localFilePath, $targetFolder, $targetFileName, (string)$conflictMode);
        }
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
    protected function getAllFileObjectsInFolder(Folder $folder)
    {
        $files = [];
        $folderQueue = [$folder];
        while (!empty($folderQueue)) {
            $folder = array_shift($folderQueue);
            foreach ($folder->getSubfolders() as $subfolder) {
                $folderQueue[] = $subfolder;
            }
            foreach ($folder->getFiles() as $file) {
                /** @var FileInterface $file */
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
     * @param string $conflictMode a value of the DuplicationBehavior enumeration
     *
     * @throws \Exception|\TYPO3\CMS\Core\Exception
     * @throws \InvalidArgumentException
     * @throws InvalidTargetFolderException
     * @return Folder
     */
    public function moveFolder(Folder $folderToMove, Folder $targetParentFolder, $newFolderName = null, $conflictMode = DuplicationBehavior::RENAME)
    {
        // @todo add tests
        $originalFolder = $folderToMove->getParentFolder();
        $this->assureFolderMovePermissions($folderToMove, $targetParentFolder);
        $sourceStorage = $folderToMove->getStorage();
        $returnObject = null;
        $sanitizedNewFolderName = $this->driver->sanitizeFileName($newFolderName ?: $folderToMove->getName());
        // @todo check if folder already exists in $targetParentFolder, handle this conflict then
        $this->emitPreFolderMoveSignal($folderToMove, $targetParentFolder, $sanitizedNewFolderName);
        // Get all file objects now so we are able to update them after moving the folder
        $fileObjects = $this->getAllFileObjectsInFolder($folderToMove);
        if ($sourceStorage === $this) {
            if ($this->isWithinFolder($folderToMove, $targetParentFolder)) {
                throw new InvalidTargetFolderException(
                    sprintf(
                        'Cannot move folder "%s" into target folder "%s", because the target folder is already within the folder to be moved!',
                        $folderToMove->getName(),
                        $targetParentFolder->getName()
                    ),
                    1422723050
                );
            }
            $fileMappings = $this->driver->moveFolderWithinStorage($folderToMove->getIdentifier(), $targetParentFolder->getIdentifier(), $sanitizedNewFolderName);
        } else {
            $fileMappings = $this->moveFolderBetweenStorages($folderToMove, $targetParentFolder, $sanitizedNewFolderName);
        }
        // Update the identifier and storage of all file objects
        foreach ($fileObjects as $oldIdentifier => $fileObject) {
            $newIdentifier = $fileMappings[$oldIdentifier];
            $fileObject->updateProperties(['storage' => $this->getUid(), 'identifier' => $newIdentifier]);
            $this->getIndexer()->updateIndexEntry($fileObject);
        }
        $returnObject = $this->getFolder($fileMappings[$folderToMove->getIdentifier()]);
        $this->emitPostFolderMoveSignal($folderToMove, $targetParentFolder, $returnObject->getName(), $originalFolder);
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
    protected function moveFolderBetweenStorages(Folder $folderToMove, Folder $targetParentFolder, $newFolderName)
    {
        throw new \RuntimeException('Not yet implemented', 1476046361);
    }

    /**
     * Copies a folder.
     *
     * @param FolderInterface $folderToCopy The folder to copy
     * @param FolderInterface $targetParentFolder The target folder
     * @param string $newFolderName
     * @param string $conflictMode a value of the DuplicationBehavior enumeration
     * @return Folder The new (copied) folder object
     * @throws InvalidTargetFolderException
     */
    public function copyFolder(FolderInterface $folderToCopy, FolderInterface $targetParentFolder, $newFolderName = null, $conflictMode = DuplicationBehavior::RENAME)
    {
        // @todo implement the $conflictMode handling
        $this->assureFolderCopyPermissions($folderToCopy, $targetParentFolder);
        $returnObject = null;
        $sanitizedNewFolderName = $this->driver->sanitizeFileName($newFolderName ?: $folderToCopy->getName());
        if ($folderToCopy instanceof Folder && $targetParentFolder instanceof Folder) {
            $this->emitPreFolderCopySignal($folderToCopy, $targetParentFolder, $sanitizedNewFolderName);
        }
        $sourceStorage = $folderToCopy->getStorage();
        // call driver method to move the file
        // that also updates the file object properties
        if ($sourceStorage === $this) {
            if ($this->isWithinFolder($folderToCopy, $targetParentFolder)) {
                throw new InvalidTargetFolderException(
                    sprintf(
                        'Cannot copy folder "%s" into target folder "%s", because the target folder is already within the folder to be copied!',
                        $folderToCopy->getName(),
                        $targetParentFolder->getName()
                    ),
                    1422723059
                );
            }
            $this->driver->copyFolderWithinStorage($folderToCopy->getIdentifier(), $targetParentFolder->getIdentifier(), $sanitizedNewFolderName);
            $returnObject = $this->getFolder($targetParentFolder->getSubfolder($sanitizedNewFolderName)->getIdentifier());
        } else {
            $this->copyFolderBetweenStorages($folderToCopy, $targetParentFolder, $sanitizedNewFolderName);
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
    protected function copyFolderBetweenStorages(Folder $folderToCopy, Folder $targetParentFolder, $newFolderName)
    {
        throw new \RuntimeException('Not yet implemented.', 1476046386);
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
    public function renameFolder($folderObject, $newName)
    {

        // Renaming the folder should check if the parent folder is writable
        // We cannot do this however because we cannot extract the parent folder from a folder currently
        if (!$this->checkFolderActionPermission('rename', $folderObject)) {
            throw new Exception\InsufficientUserPermissionsException('You are not allowed to rename the folder "' . $folderObject->getIdentifier() . '\'', 1357811441);
        }

        $sanitizedNewName = $this->driver->sanitizeFileName($newName);
        $returnObject = null;
        if ($this->driver->folderExistsInFolder($sanitizedNewName, $folderObject->getIdentifier())) {
            throw new \InvalidArgumentException('The folder ' . $sanitizedNewName . ' already exists in folder ' . $folderObject->getIdentifier(), 1325418870);
        }

        $this->emitPreFolderRenameSignal($folderObject, $sanitizedNewName);

        $fileObjects = $this->getAllFileObjectsInFolder($folderObject);
        $fileMappings = $this->driver->renameFolder($folderObject->getIdentifier(), $sanitizedNewName);
        // Update the identifier of all file objects
        foreach ($fileObjects as $oldIdentifier => $fileObject) {
            $newIdentifier = $fileMappings[$oldIdentifier];
            $fileObject->updateProperties(['identifier' => $newIdentifier]);
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
     * @throws Exception\InsufficientFolderAccessPermissionsException
     * @throws Exception\InsufficientUserPermissionsException
     * @throws Exception\FileOperationErrorException
     * @throws Exception\InvalidPathException
     * @return bool
     */
    public function deleteFolder($folderObject, $deleteRecursively = false)
    {
        $isEmpty = $this->driver->isFolderEmpty($folderObject->getIdentifier());
        $this->assureFolderDeletePermission($folderObject, ($deleteRecursively && !$isEmpty));
        if (!$isEmpty && !$deleteRecursively) {
            throw new \RuntimeException('Could not delete folder "' . $folderObject->getIdentifier() . '" because it is not empty.', 1325952534);
        }

        $this->emitPreFolderDeleteSignal($folderObject);

        foreach ($this->getFilesInFolder($folderObject, 0, 0, false, $deleteRecursively) as $file) {
            $this->deleteFile($file);
        }

        $result = $this->driver->deleteFolder($folderObject->getIdentifier(), $deleteRecursively);

        $this->emitPostFolderDeleteSignal($folderObject);

        return $result;
    }

    /**
     * Returns the Identifier for a folder within a given folder.
     *
     * @param string $folderName The name of the target folder
     * @param Folder $parentFolder
     * @param bool $returnInaccessibleFolderObject
     * @return Folder|InaccessibleFolder
     * @throws \Exception
     * @throws Exception\InsufficientFolderAccessPermissionsException
     */
    public function getFolderInFolder($folderName, Folder $parentFolder, $returnInaccessibleFolderObject = false)
    {
        $folderIdentifier = $this->driver->getFolderInFolder($folderName, $parentFolder->getIdentifier());
        return $this->getFolder($folderIdentifier, $returnInaccessibleFolderObject);
    }

    /**
     * @param Folder $folder
     * @param int $start
     * @param int $maxNumberOfItems
     * @param bool $useFilters
     * @param bool $recursive
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return Folder[]
     */
    public function getFoldersInFolder(Folder $folder, $start = 0, $maxNumberOfItems = 0, $useFilters = true, $recursive = false, $sort = '', $sortRev = false)
    {
        $filters = $useFilters == true ? $this->fileAndFolderNameFilters : [];

        $folderIdentifiers = $this->driver->getFoldersInFolder($folder->getIdentifier(), $start, $maxNumberOfItems, $recursive, $filters, $sort, $sortRev);

        // Exclude processing folders
        foreach ($this->getProcessingFolders() as $processingFolder) {
            $processingIdentifier = $processingFolder->getIdentifier();
            if (isset($folderIdentifiers[$processingIdentifier])) {
                unset($folderIdentifiers[$processingIdentifier]);
            }
        }
        $folders = [];
        foreach ($folderIdentifiers as $folderIdentifier) {
            $folders[$folderIdentifier] = $this->getFolder($folderIdentifier, true);
        }
        return $folders;
    }

    /**
     * @param Folder  $folder
     * @param bool $useFilters
     * @param bool $recursive
     * @return int Number of subfolders
     * @throws Exception\InsufficientFolderAccessPermissionsException
     */
    public function countFoldersInFolder(Folder $folder, $useFilters = true, $recursive = false)
    {
        $this->assureFolderReadPermission($folder);
        $filters = $useFilters ? $this->fileAndFolderNameFilters : [];
        return $this->driver->countFoldersInFolder($folder->getIdentifier(), $recursive, $filters);
    }

    /**
     * Returns TRUE if the specified folder exists.
     *
     * @param string $identifier
     * @return bool
     */
    public function hasFolder($identifier)
    {
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
    public function hasFolderInFolder($folderName, Folder $folder)
    {
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
     * @return Folder
     * @throws Exception\ExistingTargetFolderException
     * @throws Exception\InsufficientFolderAccessPermissionsException
     * @throws Exception\InsufficientFolderWritePermissionsException
     * @throws \Exception
     */
    public function createFolder($folderName, Folder $parentFolder = null)
    {
        if ($parentFolder === null) {
            $parentFolder = $this->getRootLevelFolder();
        } elseif (!$this->driver->folderExists($parentFolder->getIdentifier())) {
            throw new \InvalidArgumentException('Parent folder "' . $parentFolder->getIdentifier() . '" does not exist.', 1325689164);
        }
        if (!$this->checkFolderActionPermission('add', $parentFolder)) {
            throw new Exception\InsufficientFolderWritePermissionsException('You are not allowed to create directories in the folder "' . $parentFolder->getIdentifier() . '"', 1323059807);
        }
        if ($this->driver->folderExistsInFolder($folderName, $parentFolder->getIdentifier())) {
            throw new Exception\ExistingTargetFolderException('Folder "' . $folderName . '" already exists.', 1423347324);
        }

        $this->emitPreFolderAddSignal($parentFolder, $folderName);

        $newFolder = $this->getDriver()->createFolder($folderName, $parentFolder->getIdentifier(), true);
        $newFolder = $this->getFolder($newFolder);

        $this->emitPostFolderAddSignal($newFolder);

        return $newFolder;
    }

    /**
     * Retrieves information about a folder
     *
     * @param Folder $folder
     * @return array
     */
    public function getFolderInfo(Folder $folder)
    {
        return $this->driver->getFolderInfoByIdentifier($folder->getIdentifier());
    }

    /**
     * Returns the default folder where new files are stored if no other folder is given.
     *
     * @return Folder
     */
    public function getDefaultFolder()
    {
        return $this->getFolder($this->driver->getDefaultFolder());
    }

    /**
     * @param string $identifier
     * @param bool $returnInaccessibleFolderObject
     *
     * @return Folder|InaccessibleFolder
     * @throws \Exception
     * @throws Exception\InsufficientFolderAccessPermissionsException
     */
    public function getFolder($identifier, $returnInaccessibleFolderObject = false)
    {
        $data = $this->driver->getFolderInfoByIdentifier($identifier);
        $folder = $this->getResourceFactoryInstance()->createFolderObject($this, $data['identifier'], $data['name']);

        try {
            $this->assureFolderReadPermission($folder);
        } catch (Exception\InsufficientFolderAccessPermissionsException $e) {
            $folder = null;
            if ($returnInaccessibleFolderObject) {
                // if parent folder is readable return inaccessible folder object
                $parentPermissions = $this->driver->getPermissions($this->driver->getParentFolderIdentifierOfIdentifier($identifier));
                if ($parentPermissions['r']) {
                    $folder = GeneralUtility::makeInstance(
                        InaccessibleFolder::class,
                        $this,
                        $data['identifier'],
                        $data['name']
                    );
                }
            }

            if ($folder === null) {
                throw $e;
            }
        }
        return $folder;
    }

    /**
     * Returns TRUE if the specified file is in a folder that is set a processing for a storage
     *
     * @param string $identifier
     * @return bool
     */
    public function isWithinProcessingFolder($identifier)
    {
        $inProcessingFolder = false;
        foreach ($this->getProcessingFolders() as $processingFolder) {
            if ($this->driver->isWithin($processingFolder->getIdentifier(), $identifier)) {
                $inProcessingFolder = true;
                break;
            }
        }
        return $inProcessingFolder;
    }

    /**
     * Checks if a resource (file or folder) is within the given folder
     *
     * @param Folder $folder
     * @param ResourceInterface $resource
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isWithinFolder(Folder $folder, ResourceInterface $resource)
    {
        if ($folder->getStorage() !== $this) {
            throw new \InvalidArgumentException('Given folder "' . $folder->getIdentifier() . '" is not part of this storage!', 1422709241);
        }
        if ($folder->getStorage() !== $resource->getStorage()) {
            return false;
        }
        return $this->driver->isWithin($folder->getIdentifier(), $resource->getIdentifier());
    }

    /**
     * Returns the folders on the root level of the storage
     * or the first mount point of this storage for this user
     * if $respectFileMounts is set.
     *
     * @param bool $respectFileMounts
     * @return Folder
     */
    public function getRootLevelFolder($respectFileMounts = true)
    {
        if ($respectFileMounts && !empty($this->fileMounts)) {
            $mount = reset($this->fileMounts);
            return $mount['folder'];
        }
        return $this->getResourceFactoryInstance()->createFolderObject($this, $this->driver->getRootLevelFolder(), '');
    }

    /**
     * Emits sanitize fileName signal.
     *
     * @param string $fileName
     * @param Folder $targetFolder
     * @return string Modified target file name
     */
    protected function emitSanitizeFileNameSignal($fileName, Folder $targetFolder)
    {
        list($fileName) = $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_SanitizeFileName, [$fileName, $targetFolder, $this, $this->driver]);
        return $fileName;
    }

    /**
     * Emits file pre-add signal.
     *
     * @param string $targetFileName
     * @param Folder $targetFolder
     * @param string $sourceFilePath
     * @return string Modified target file name
     */
    protected function emitPreFileAddSignal($targetFileName, Folder $targetFolder, $sourceFilePath)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreFileAdd, [&$targetFileName, $targetFolder, $sourceFilePath, $this, $this->driver]);
        return $targetFileName;
    }

    /**
     * Emits the file post-add signal.
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     */
    protected function emitPostFileAddSignal(FileInterface $file, Folder $targetFolder)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFileAdd, [$file, $targetFolder]);
    }

    /**
     * Emits file pre-copy signal.
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     */
    protected function emitPreFileCopySignal(FileInterface $file, Folder $targetFolder)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreFileCopy, [$file, $targetFolder]);
    }

    /**
     * Emits the file post-copy signal.
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     */
    protected function emitPostFileCopySignal(FileInterface $file, Folder $targetFolder)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFileCopy, [$file, $targetFolder]);
    }

    /**
     * Emits the file pre-move signal.
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     */
    protected function emitPreFileMoveSignal(FileInterface $file, Folder $targetFolder)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreFileMove, [$file, $targetFolder]);
    }

    /**
     * Emits the file post-move signal.
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     * @param FolderInterface $originalFolder
     */
    protected function emitPostFileMoveSignal(FileInterface $file, Folder $targetFolder, FolderInterface $originalFolder)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFileMove, [$file, $targetFolder, $originalFolder]);
    }

    /**
     * Emits the file pre-rename signal
     *
     * @param FileInterface $file
     * @param $targetFolder
     */
    protected function emitPreFileRenameSignal(FileInterface $file, $targetFolder)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreFileRename, [$file, $targetFolder]);
    }

    /**
     * Emits the file post-rename signal.
     *
     * @param FileInterface $file
     * @param string $sanitizedTargetFileName
     */
    protected function emitPostFileRenameSignal(FileInterface $file, $sanitizedTargetFileName)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFileRename, [$file, $sanitizedTargetFileName]);
    }

    /**
     * Emits the file pre-replace signal.
     *
     * @param FileInterface $file
     * @param $localFilePath
     */
    protected function emitPreFileReplaceSignal(FileInterface $file, $localFilePath)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreFileReplace, [$file, $localFilePath]);
    }

    /**
     * Emits the file post-replace signal
     *
     * @param FileInterface $file
     * @param string $localFilePath
     */
    protected function emitPostFileReplaceSignal(FileInterface $file, $localFilePath)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFileReplace, [$file, $localFilePath]);
    }

    /**
     * Emits the file post-create signal
     *
     * @param string $newFileIdentifier
     * @param Folder $targetFolder
     */
    protected function emitPostFileCreateSignal($newFileIdentifier, Folder $targetFolder)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFileCreate, [$newFileIdentifier, $targetFolder]);
    }

    /**
     * Emits the file pre-deletion signal.
     *
     * @param FileInterface $file
     */
    protected function emitPreFileDeleteSignal(FileInterface $file)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreFileDelete, [$file]);
    }

    /**
     * Emits the file post-deletion signal
     *
     * @param FileInterface $file
     */
    protected function emitPostFileDeleteSignal(FileInterface $file)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFileDelete, [$file]);
    }

    /**
     * Emits the file post-set-contents signal
     *
     * @param FileInterface $file
     * @param mixed $content
     */
    protected function emitPostFileSetContentsSignal(FileInterface $file, $content)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFileSetContents, [$file, $content]);
    }

    /**
     * Emits the folder pre-add signal.
     *
     * @param Folder $targetFolder
     * @param string $name
     */
    protected function emitPreFolderAddSignal(Folder $targetFolder, $name)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreFolderAdd, [$targetFolder, $name]);
    }

    /**
     * Emits the folder post-add signal.
     *
     * @param Folder $folder
     */
    protected function emitPostFolderAddSignal(Folder $folder)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFolderAdd, [$folder]);
    }

    /**
     * Emits the folder pre-copy signal.
     *
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param $newName
     */
    protected function emitPreFolderCopySignal(Folder $folder, Folder $targetFolder, $newName)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreFolderCopy, [$folder, $targetFolder, $newName]);
    }

    /**
     * Emits the folder post-copy signal.
     *
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param $newName
     */
    protected function emitPostFolderCopySignal(Folder $folder, Folder $targetFolder, $newName)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFolderCopy, [$folder, $targetFolder, $newName]);
    }

    /**
     * Emits the folder pre-move signal.
     *
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param $newName
     */
    protected function emitPreFolderMoveSignal(Folder $folder, Folder $targetFolder, $newName)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreFolderMove, [$folder, $targetFolder, $newName]);
    }

    /**
     * Emits the folder post-move signal.
     *
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param string $newName
     * @param Folder $originalFolder
     */
    protected function emitPostFolderMoveSignal(Folder $folder, Folder $targetFolder, $newName, Folder $originalFolder)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFolderMove, [$folder, $targetFolder, $newName, $originalFolder]);
    }

    /**
     * Emits the folder pre-rename signal.
     *
     * @param Folder $folder
     * @param string $newName
     */
    protected function emitPreFolderRenameSignal(Folder $folder, $newName)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreFolderRename, [$folder, $newName]);
    }

    /**
     * Emits the folder post-rename signal.
     *
     * @param Folder $folder
     * @param string $newName
     */
    protected function emitPostFolderRenameSignal(Folder $folder, $newName)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFolderRename, [$folder, $newName]);
    }

    /**
     * Emits the folder pre-deletion signal.
     *
     * @param Folder $folder
     */
    protected function emitPreFolderDeleteSignal(Folder $folder)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreFolderDelete, [$folder]);
    }

    /**
     * Emits folder post-deletion signal..
     *
     * @param Folder $folder
     */
    protected function emitPostFolderDeleteSignal(Folder $folder)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PostFolderDelete, [$folder]);
    }

    /**
     * Emits file pre-processing signal when generating a public url for a file or folder.
     *
     * @param ResourceInterface $resourceObject
     * @param bool $relativeToCurrentScript
     * @param array $urlData
     */
    protected function emitPreGeneratePublicUrlSignal(ResourceInterface $resourceObject, $relativeToCurrentScript, array $urlData)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, self::SIGNAL_PreGeneratePublicUrl, [$this, $this->driver, $resourceObject, $relativeToCurrentScript, $urlData]);
    }

    /**
     * Returns the destination path/fileName of a unique fileName/foldername in that path.
     * If $theFile exists in $theDest (directory) the file have numbers appended up to $this->maxNumber. Hereafter a unique string will be appended.
     * This function is used by fx. DataHandler when files are attached to records and needs to be uniquely named in the uploads/* folders
     *
     * @param FolderInterface $folder
     * @param string $theFile The input fileName to check
     * @param bool $dontCheckForUnique If set the fileName is returned with the path prepended without checking whether it already existed!
     *
     * @throws \RuntimeException
     * @return string A unique fileName inside $folder, based on $theFile.
     * @see \TYPO3\CMS\Core\Utility\File\BasicFileUtility::getUniqueName()
     */
    protected function getUniqueName(FolderInterface $folder, $theFile, $dontCheckForUnique = false)
    {
        static $maxNumber = 99, $uniqueNamePrefix = '';
        // Fetches info about path, name, extension of $theFile
        $origFileInfo = PathUtility::pathinfo($theFile);
        // Adds prefix
        if ($uniqueNamePrefix) {
            $origFileInfo['basename'] = $uniqueNamePrefix . $origFileInfo['basename'];
            $origFileInfo['filename'] = $uniqueNamePrefix . $origFileInfo['filename'];
        }
        // Check if the file exists and if not - return the fileName...
        // The destinations file
        $theDestFile = $origFileInfo['basename'];
        // If the file does NOT exist we return this fileName
        if (!$this->driver->fileExistsInFolder($theDestFile, $folder->getIdentifier()) || $dontCheckForUnique) {
            return $theDestFile;
        }
        // Well the fileName in its pure form existed. Now we try to append
        // numbers / unique-strings and see if we can find an available fileName
        // This removes _xx if appended to the file
        $theTempFileBody = preg_replace('/_[0-9][0-9]$/', '', $origFileInfo['filename']);
        $theOrigExt = $origFileInfo['extension'] ? '.' . $origFileInfo['extension'] : '';
        for ($a = 1; $a <= $maxNumber + 1; $a++) {
            // First we try to append numbers
            if ($a <= $maxNumber) {
                $insert = '_' . sprintf('%02d', $a);
            } else {
                $insert = '_' . substr(md5(uniqid('', true)), 0, 6);
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
     * @return Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        if (!isset($this->signalSlotDispatcher)) {
            $this->signalSlotDispatcher = $this->getObjectManager()->get(Dispatcher::class);
        }
        return $this->signalSlotDispatcher;
    }

    /**
     * Gets the ObjectManager.
     *
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * @return ResourceFactory
     */
    protected function getFileFactory()
    {
        return GeneralUtility::makeInstance(ResourceFactory::class);
    }

    /**
     * @return Index\FileIndexRepository
     */
    protected function getFileIndexRepository()
    {
        return FileIndexRepository::getInstance();
    }

    /**
     * @return Service\FileProcessingService
     */
    protected function getFileProcessingService()
    {
        if (!$this->fileProcessingService) {
            $this->fileProcessingService = GeneralUtility::makeInstance(Service\FileProcessingService::class, $this, $this->driver);
        }
        return $this->fileProcessingService;
    }

    /**
     * Gets the role of a folder.
     *
     * @param FolderInterface $folder Folder object to get the role from
     * @return string The role the folder has
     */
    public function getRole(FolderInterface $folder)
    {
        $folderRole = FolderInterface::ROLE_DEFAULT;
        $identifier = $folder->getIdentifier();
        if (method_exists($this->driver, 'getRole')) {
            $folderRole = $this->driver->getRole($folder->getIdentifier());
        }
        if (isset($this->fileMounts[$identifier])) {
            $folderRole = FolderInterface::ROLE_MOUNT;

            if (!empty($this->fileMounts[$identifier]['read_only'])) {
                $folderRole = FolderInterface::ROLE_READONLY_MOUNT;
            }
            if ($this->fileMounts[$identifier]['user_mount']) {
                $folderRole = FolderInterface::ROLE_USER_MOUNT;
            }
        }
        if ($folder instanceof Folder && $this->isProcessingFolder($folder)) {
            $folderRole = FolderInterface::ROLE_PROCESSING;
        }

        return $folderRole;
    }

    /**
     * Getter function to return the folder where the files can
     * be processed. Does not check for access rights here.
     *
     * @param File $file Specific file you want to have the processing folder for
     * @return Folder
     */
    public function getProcessingFolder(File $file = null)
    {
        if (!isset($this->processingFolder)) {
            $processingFolder = self::DEFAULT_ProcessingFolder;
            if (!empty($this->storageRecord['processingfolder'])) {
                $processingFolder = $this->storageRecord['processingfolder'];
            }
            try {
                if (strpos($processingFolder, ':') !== false) {
                    list($storageUid, $processingFolderIdentifier) = explode(':', $processingFolder, 2);
                    $storage = $this->getResourceFactoryInstance()->getStorageObject($storageUid);
                    if ($storage->hasFolder($processingFolderIdentifier)) {
                        $this->processingFolder = $storage->getFolder($processingFolderIdentifier);
                    } else {
                        $rootFolder = $storage->getRootLevelFolder(false);
                        $currentEvaluatePermissions = $storage->getEvaluatePermissions();
                        $storage->setEvaluatePermissions(false);
                        $this->processingFolder = $storage->createFolder(
                            ltrim($processingFolderIdentifier, '/'),
                            $rootFolder
                        );
                        $storage->setEvaluatePermissions($currentEvaluatePermissions);
                    }
                } else {
                    if ($this->driver->folderExists($processingFolder) === false) {
                        $rootFolder = $this->getRootLevelFolder(false);
                        try {
                            $currentEvaluatePermissions = $this->evaluatePermissions;
                            $this->evaluatePermissions = false;
                            $this->processingFolder = $this->createFolder(
                                $processingFolder,
                                $rootFolder
                            );
                            $this->evaluatePermissions = $currentEvaluatePermissions;
                        } catch (\InvalidArgumentException $e) {
                            $this->processingFolder = GeneralUtility::makeInstance(
                                InaccessibleFolder::class,
                                $this,
                                $processingFolder,
                                $processingFolder
                            );
                        }
                    } else {
                        $data = $this->driver->getFolderInfoByIdentifier($processingFolder);
                        $this->processingFolder = $this->getResourceFactoryInstance()->createFolderObject($this, $data['identifier'], $data['name']);
                    }
                }
            } catch (Exception\InsufficientFolderWritePermissionsException $e) {
                $this->processingFolder = GeneralUtility::makeInstance(
                    InaccessibleFolder::class,
                    $this,
                    $processingFolder,
                    $processingFolder
                );
            } catch (Exception\ResourcePermissionsUnavailableException $e) {
                $this->processingFolder = GeneralUtility::makeInstance(
                    InaccessibleFolder::class,
                    $this,
                    $processingFolder,
                    $processingFolder
                );
            }
        }

        $processingFolder = $this->processingFolder;
        if (!empty($file)) {
            $processingFolder = $this->getNestedProcessingFolder($file, $processingFolder);
        }
        return $processingFolder;
    }

    /**
     * Getter function to return the the file's corresponding hashed subfolder
     * of the processed folder
     *
     * @param File $file
     * @param Folder $rootProcessingFolder
     * @return Folder
     * @throws Exception\InsufficientFolderWritePermissionsException
     */
    protected function getNestedProcessingFolder(File $file, Folder $rootProcessingFolder)
    {
        $processingFolder = $rootProcessingFolder;
        $nestedFolderNames = $this->getNamesForNestedProcessingFolder(
            $file->getIdentifier(),
            self::PROCESSING_FOLDER_LEVELS
        );

        try {
            foreach ($nestedFolderNames as $folderName) {
                if ($processingFolder->hasFolder($folderName)) {
                    $processingFolder = $processingFolder->getSubfolder($folderName);
                } else {
                    $currentEvaluatePermissions = $processingFolder->getStorage()->getEvaluatePermissions();
                    $processingFolder->getStorage()->setEvaluatePermissions(false);
                    $processingFolder = $processingFolder->createFolder($folderName);
                    $processingFolder->getStorage()->setEvaluatePermissions($currentEvaluatePermissions);
                }
            }
        } catch (Exception\FolderDoesNotExistException $e) {
        }

        return $processingFolder;
    }

    /**
     * Generates appropriate hashed sub-folder path for a given file identifier
     *
     * @param string $fileIdentifier
     * @param int $levels
     * @return string[]
     */
    protected function getNamesForNestedProcessingFolder($fileIdentifier, $levels)
    {
        $names = [];
        if ($levels === 0) {
            return $names;
        }
        $hash = md5($fileIdentifier);
        for ($i = 1; $i <= $levels; $i++) {
            $names[] = substr($hash, $i, 1);
        }
        return $names;
    }

    /**
     * Gets the driver Type configured for this storage.
     *
     * @return string
     */
    public function getDriverType()
    {
        return $this->storageRecord['driver'];
    }

    /**
     * Gets the Indexer.
     *
     * @return Index\Indexer
     */
    protected function getIndexer()
    {
        return GeneralUtility::makeInstance(Index\Indexer::class, $this);
    }

    /**
     * @param bool $isDefault
     */
    public function setDefault($isDefault)
    {
        $this->isDefault = (bool)$isDefault;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->isDefault;
    }

    /**
     * @return ResourceFactory
     */
    public function getResourceFactoryInstance(): ResourceFactory
    {
        return ResourceFactory::getInstance();
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get the nearest Recycler folder for given file
     *
     * Return null if:
     *  - There is no folder with ROLE_RECYCLER in the rootline of the given File
     *  - File is a ProcessedFile (we don't know the concept of recycler folders for processedFiles)
     *  - File is located in a folder with ROLE_RECYCLER
     *
     * @param FileInterface $file
     * @return Folder|null
     */
    protected function getNearestRecyclerFolder(FileInterface $file)
    {
        if ($file instanceof ProcessedFile) {
            return null;
        }

        $recyclerFolder = null;
        $folder = $file->getParentFolder();

        do {
            if ($folder->getRole() === FolderInterface::ROLE_RECYCLER) {
                break;
            }

            foreach ($folder->getSubfolders() as $subFolder) {
                if ($subFolder->getRole() === FolderInterface::ROLE_RECYCLER) {
                    $recyclerFolder = $subFolder;
                    break;
                }
            }

            $parentFolder = $folder->getParentFolder();
            $isFolderLoop = $folder->getIdentifier() === $parentFolder->getIdentifier();
            $folder = $parentFolder;
        } while ($recyclerFolder === null && !$isFolderLoop);

        return $recyclerFolder;
    }

    /**
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {
        /** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
        $logManager = GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Log\LogManager::class
        );
        return $logManager->getLogger(get_class($this));
    }
}
