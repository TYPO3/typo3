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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Factory class for FAL objects
 * @todo implement constructor-level caching
 */
class ResourceFactory implements ResourceFactoryInterface, \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Gets a singleton instance of this class.
     *
     * @return ResourceFactory
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(__CLASS__);
    }

    /**
     * @var ResourceStorage[]
     */
    protected $storageInstances = [];

    /**
     * @var Collection\AbstractFileCollection[]
     */
    protected $collectionInstances = [];

    /**
     * @var File[]
     */
    protected $fileInstances = [];

    /**
     * @var FileReference[]
     */
    protected $fileReferenceInstances = [];

    /**
     * A list of the base paths of "local" driver storages. Used to make the detection of base paths easier.
     *
     * @var array<int, LocalPath>|null
     */
    protected $localDriverStorageCache;

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Inject signal slot dispatcher
     *
     * @param Dispatcher $signalSlotDispatcher an instance of the signal slot dispatcher
     */
    public function __construct(Dispatcher $signalSlotDispatcher = null)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher ?: GeneralUtility::makeInstance(Dispatcher::class);
    }

    /**
     * Creates a driver object for a specified storage object.
     *
     * @param string $driverIdentificationString The driver class (or identifier) to use.
     * @param array $driverConfiguration The configuration of the storage
     * @return Driver\DriverInterface
     * @throws \InvalidArgumentException
     */
    public function getDriverObject($driverIdentificationString, array $driverConfiguration)
    {
        /** @var Driver\DriverRegistry $driverRegistry */
        $driverRegistry = GeneralUtility::makeInstance(Driver\DriverRegistry::class);
        $driverClass = $driverRegistry->getDriverClass($driverIdentificationString);
        $driverObject = GeneralUtility::makeInstance($driverClass, $driverConfiguration);
        return $driverObject;
    }

    /**
     * Returns the Default Storage
     *
     * The Default Storage is considered to be the replacement for the fileadmin/ construct.
     * It is automatically created with the setting fileadminDir from install tool.
     * getDefaultStorage->getDefaultFolder() will get you fileadmin/user_upload/ in a standard
     * TYPO3 installation.
     *
     * @return ResourceStorage|null
     */
    public function getDefaultStorage()
    {
        /** @var StorageRepository $storageRepository */
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);

        $allStorages = $storageRepository->findAll();
        foreach ($allStorages as $storage) {
            if ($storage->isDefault()) {
                return $storage;
            }
        }
        return null;
    }
    /**
     * Creates an instance of the storage from given UID. The $recordData can
     * be supplied to increase performance.
     *
     * @param int $uid The uid of the storage to instantiate.
     * @param array $recordData The record row from database.
     * @param string $fileIdentifier Identifier for a file. Used for auto-detection of a storage, but only if $uid === 0 (Local default storage) is used
     *
     * @throws \InvalidArgumentException
     * @return ResourceStorage
     */
    public function getStorageObject($uid, array $recordData = [], &$fileIdentifier = null)
    {
        if (!is_numeric($uid)) {
            throw new \InvalidArgumentException('The UID of storage has to be numeric. UID given: "' . $uid . '"', 1314085991);
        }
        $uid = (int)$uid;
        if ($uid === 0 && $fileIdentifier !== null) {
            $uid = $this->findBestMatchingStorageByLocalPath($fileIdentifier);
        }
        if (empty($this->storageInstances[$uid])) {
            $storageConfiguration = null;
            list($_, $uid, $recordData, $fileIdentifier) = $this->emitPreProcessStorageSignal($uid, $recordData, $fileIdentifier);
            // If the built-in storage with UID=0 is requested:
            if ($uid === 0) {
                $recordData = [
                    'uid' => 0,
                    'pid' => 0,
                    'name' => 'Fallback Storage',
                    'description' => 'Internal storage, mounting the main TYPO3_site directory.',
                    'driver' => 'Local',
                    'processingfolder' => 'typo3temp/assets/_processed_/',
                    // legacy code
                    'configuration' => '',
                    'is_online' => true,
                    'is_browsable' => true,
                    'is_public' => true,
                    'is_writable' => true,
                    'is_default' => false,
                ];
                $storageConfiguration = [
                    'basePath' => '/',
                    'pathType' => 'relative'
                ];
            } elseif ($recordData === [] || (int)$recordData['uid'] !== $uid) {
                /** @var StorageRepository $storageRepository */
                $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
                $recordData = $storageRepository->fetchRowByUid($uid);
            }
            $storageObject = $this->createStorageObject($recordData, $storageConfiguration);
            $this->emitPostProcessStorageSignal($storageObject);
            $this->storageInstances[$uid] = $storageObject;
        }
        return $this->storageInstances[$uid];
    }

    /**
     * Emits a signal before a resource storage was initialized
     *
     * @param int $uid
     * @param array $recordData
     * @param string $fileIdentifier
     * @return mixed
     */
    protected function emitPreProcessStorageSignal($uid, $recordData, $fileIdentifier)
    {
        return $this->signalSlotDispatcher->dispatch(\TYPO3\CMS\Core\Resource\ResourceFactory::class, self::SIGNAL_PreProcessStorage, [$this, $uid, $recordData, $fileIdentifier]);
    }

    /**
     * Emits a signal after a resource storage was initialized
     *
     * @param ResourceStorage $storageObject
     */
    protected function emitPostProcessStorageSignal(ResourceStorage $storageObject)
    {
        $this->signalSlotDispatcher->dispatch(self::class, self::SIGNAL_PostProcessStorage, [$this, $storageObject]);
    }

    /**
     * Checks whether a file resides within a real storage in local file system.
     * If no match is found, uid 0 is returned which is a fallback storage pointing to fileadmin in public web path.
     *
     * The file identifier is adapted accordingly to match the new storage's base path.
     *
     * @param string $localPath
     *
     * @return int
     */
    protected function findBestMatchingStorageByLocalPath(&$localPath)
    {
        if ($this->localDriverStorageCache === null) {
            $this->initializeLocalStorageCache();
        }

        // normalize path information (`//`, `../`)
        $localPath = PathUtility::getCanonicalPath($localPath);
        if ($localPath[0] !== '/') {
            $localPath = '/' . $localPath;
        }
        $bestMatchStorageUid = 0;
        $bestMatchLength = 0;
        foreach ($this->localDriverStorageCache as $storageUid => $basePath) {
            // try to match (resolved) relative base-path
            if ($basePath->getRelative() !== null
                && null !== $commonPrefix = PathUtility::getCommonPrefix([$basePath->getRelative(), $localPath])
            ) {
                $matchLength = strlen($commonPrefix);
                $basePathLength = strlen($basePath->getRelative());
                if ($matchLength >= $basePathLength && $matchLength > $bestMatchLength) {
                    $bestMatchStorageUid = $storageUid;
                    $bestMatchLength = $matchLength;
                }
            }
            // try to match (resolved) absolute base-path
            if (null !== $commonPrefix = PathUtility::getCommonPrefix([$basePath->getAbsolute(), $localPath])) {
                $matchLength = strlen($commonPrefix);
                $basePathLength = strlen($basePath->getAbsolute());
                if ($matchLength >= $basePathLength && $matchLength > $bestMatchLength) {
                    $bestMatchStorageUid = $storageUid;
                    $bestMatchLength = $matchLength;
                }
            }
        }
        if ($bestMatchLength > 0) {
            // $commonPrefix always has trailing slash, which needs to be excluded
            // (commonPrefix: /some/path/, localPath: /some/path/file.png --> /file.png; keep leading slash)
            $localPath = substr($localPath, $bestMatchLength - 1);
        }
        return $bestMatchStorageUid;
    }

    /**
     * Creates an array mapping all uids to the basePath of storages using the "local" driver.
     */
    protected function initializeLocalStorageCache()
    {
        /** @var StorageRepository $storageRepository */
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        /** @var ResourceStorage[] $storageObjects */
        $storageObjects = $storageRepository->findByStorageType('Local');

        $this->localDriverStorageCache = [
            // implicit legacy storage in project's public path
            0 => new LocalPath('/', LocalPath::TYPE_RELATIVE)
        ];
        foreach ($storageObjects as $localStorage) {
            $configuration = $localStorage->getConfiguration();
            if (!isset($configuration['basePath']) || !isset($configuration['pathType'])) {
                continue;
            }
            if ($configuration['pathType'] === 'relative') {
                $pathType = LocalPath::TYPE_RELATIVE;
            } elseif ($configuration['pathType'] === 'absolute') {
                $pathType = LocalPath::TYPE_ABSOLUTE;
            } else {
                continue;
            }
            $this->localDriverStorageCache[$localStorage->getUid()] = GeneralUtility::makeInstance(
                LocalPath::class,
                $configuration['basePath'],
                $pathType
            );
        }
    }

    /**
     * Converts a flexform data string to a flat array with key value pairs
     *
     * @param string $flexFormData
     * @return array Array with key => value pairs of the field data in the FlexForm
     */
    public function convertFlexFormDataToConfigurationArray($flexFormData)
    {
        $configuration = [];
        if ($flexFormData) {
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
            $configuration = $flexFormService->convertFlexFormContentToArray($flexFormData);
        }
        return $configuration;
    }

    /**
     * Creates an instance of the collection from given UID. The $recordData can be supplied to increase performance.
     *
     * @param int $uid The uid of the collection to instantiate.
     * @param array $recordData The record row from database.
     *
     * @throws \InvalidArgumentException
     * @return Collection\AbstractFileCollection
     */
    public function getCollectionObject($uid, array $recordData = [])
    {
        if (!is_numeric($uid)) {
            throw new \InvalidArgumentException('The UID of collection has to be numeric. UID given: "' . $uid . '"', 1314085999);
        }
        if (!$this->collectionInstances[$uid]) {
            // Get mount data if not already supplied as argument to this function
            if (empty($recordData) || $recordData['uid'] !== $uid) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_collection');
                $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $recordData = $queryBuilder->select('*')
                    ->from('sys_file_collection')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                        )
                    )
                    ->execute()
                    ->fetch();
                if (empty($recordData)) {
                    throw new \InvalidArgumentException('No collection found for given UID: "' . $uid . '"', 1314085992);
                }
            }
            $collectionObject = $this->createCollectionObject($recordData);
            $this->collectionInstances[$uid] = $collectionObject;
        }
        return $this->collectionInstances[$uid];
    }

    /**
     * Creates a collection object.
     *
     * @param array $collectionData The database row of the sys_file_collection record.
     * @return Collection\AbstractFileCollection
     */
    public function createCollectionObject(array $collectionData)
    {
        /** @var Collection\FileCollectionRegistry $registry */
        $registry = GeneralUtility::makeInstance(Collection\FileCollectionRegistry::class);

        /** @var \TYPO3\CMS\Core\Collection\AbstractRecordCollection $class */
        $class = $registry->getFileCollectionClass($collectionData['type']);

        return $class::create($collectionData);
    }

    /**
     * Creates a storage object from a storage database row.
     *
     * @param array $storageRecord
     * @param array $storageConfiguration Storage configuration (if given, this won't be extracted from the FlexForm value but the supplied array used instead)
     * @return ResourceStorage
     */
    public function createStorageObject(array $storageRecord, array $storageConfiguration = null)
    {
        if (!$storageConfiguration) {
            $storageConfiguration = $this->convertFlexFormDataToConfigurationArray($storageRecord['configuration']);
        }
        $driverType = $storageRecord['driver'];
        $driverObject = $this->getDriverObject($driverType, $storageConfiguration);
        return GeneralUtility::makeInstance(ResourceStorage::class, $driverObject, $storageRecord);
    }

    /**
     * Creates a folder to directly access (a part of) a storage.
     *
     * @param ResourceStorage $storage The storage the folder belongs to
     * @param string $identifier The path to the folder. Might also be a simple unique string, depending on the storage driver.
     * @param string $name The name of the folder (e.g. the folder name)
     * @return Folder
     */
    public function createFolderObject(ResourceStorage $storage, $identifier, $name)
    {
        return GeneralUtility::makeInstance(Folder::class, $storage, $identifier, $name);
    }

    /**
     * Creates an instance of the file given UID. The $fileData can be supplied
     * to increase performance.
     *
     * @param int $uid The uid of the file to instantiate.
     * @param array $fileData The record row from database.
     *
     * @throws \InvalidArgumentException
     * @throws Exception\FileDoesNotExistException
     * @return File
     */
    public function getFileObject($uid, array $fileData = [])
    {
        if (!is_numeric($uid)) {
            throw new \InvalidArgumentException('The UID of file has to be numeric. UID given: "' . $uid . '"', 1300096564);
        }
        if (empty($this->fileInstances[$uid])) {
            // Fetches data in case $fileData is empty
            if (empty($fileData)) {
                $fileData = $this->getFileIndexRepository()->findOneByUid($uid);
                if ($fileData === false) {
                    throw new Exception\FileDoesNotExistException('No file found for given UID: ' . $uid, 1317178604);
                }
            }
            $this->fileInstances[$uid] = $this->createFileObject($fileData);
        }
        return $this->fileInstances[$uid];
    }

    /**
     * Gets an file object from an identifier [storage]:[fileId]
     *
     * @param string $identifier
     * @return File|ProcessedFile|null
     * @throws \InvalidArgumentException
     */
    public function getFileObjectFromCombinedIdentifier($identifier)
    {
        if (!isset($identifier) || !is_string($identifier) || $identifier === '') {
            throw new \InvalidArgumentException('Invalid file identifier given. It must be of type string and not empty. "' . gettype($identifier) . '" given.', 1401732564);
        }
        $parts = GeneralUtility::trimExplode(':', $identifier);
        if (count($parts) === 2) {
            $storageUid = $parts[0];
            $fileIdentifier = $parts[1];
        } else {
            // We only got a path: Go into backwards compatibility mode and
            // use virtual Storage (uid=0)
            $storageUid = 0;
            $fileIdentifier = $parts[0];
        }

        // please note that getStorageObject() might modify $fileIdentifier when
        // auto-detecting the best-matching storage to use
        return $this->getFileObjectByStorageAndIdentifier($storageUid, $fileIdentifier);
    }

    /**
     * Gets an file object from storage by file identifier
     * If the file is outside of the process folder it gets indexed and returned as file object afterwards
     * If the file is within processing folder the file object will be directly returned
     *
     * @param int $storageUid
     * @param string $fileIdentifier
     * @return File|ProcessedFile|null
     */
    public function getFileObjectByStorageAndIdentifier($storageUid, &$fileIdentifier)
    {
        $storage = $this->getStorageObject($storageUid, [], $fileIdentifier);
        if (!$storage->isWithinProcessingFolder($fileIdentifier)) {
            $fileData = $this->getFileIndexRepository()->findOneByStorageUidAndIdentifier($storage->getUid(), $fileIdentifier);
            if ($fileData === false) {
                $fileObject = $this->getIndexer($storage)->createIndexEntry($fileIdentifier);
            } else {
                $fileObject = $this->getFileObject($fileData['uid'], $fileData);
            }
        } else {
            $fileObject = $this->getProcessedFileRepository()->findByStorageAndIdentifier($storage, $fileIdentifier);
        }

        return $fileObject;
    }

    /**
     * Bulk function, can be used for anything to get a file or folder
     *
     * 1. It's a UID
     * 2. It's a combined identifier
     * 3. It's just a path/filename (coming from the oldstyle/backwards compatibility)
     *
     * Files, previously laid on fileadmin/ or something, will be "mapped" to the storage the file is
     * in now. Files like typo3temp/ or typo3conf/ will be moved to the first writable storage
     * in its processing folder
     *
     * $input could be
     * - "2:myfolder/myfile.jpg" (combined identifier)
     * - "23" (file UID)
     * - "uploads/myfile.png" (backwards-compatibility, storage "0")
     * - "file:23"
     *
     * @param string $input
     * @return File|Folder|null
     */
    public function retrieveFileOrFolderObject($input)
    {
        // Remove Environment::getPublicPath() because absolute paths under Windows systems contain ':'
        // This is done in all considered sub functions anyway
        $input = str_replace(Environment::getPublicPath() . '/', '', $input);

        if (GeneralUtility::isFirstPartOfStr($input, 'file:')) {
            $input = substr($input, 5);
            return $this->retrieveFileOrFolderObject($input);
        }
        if (MathUtility::canBeInterpretedAsInteger($input)) {
            return $this->getFileObject($input);
        }
        if (strpos($input, ':') > 0) {
            list($prefix) = explode(':', $input);
            if (MathUtility::canBeInterpretedAsInteger($prefix)) {
                // path or folder in a valid storageUID
                return $this->getObjectFromCombinedIdentifier($input);
            }
            if ($prefix === 'EXT') {
                $input = GeneralUtility::getFileAbsFileName($input);
                if (empty($input)) {
                    return null;
                }

                $input = PathUtility::getRelativePath(Environment::getPublicPath() . '/', PathUtility::dirname($input)) . PathUtility::basename($input);
                return $this->getFileObjectFromCombinedIdentifier($input);
            }
            return null;
        }
        // this is a backwards-compatible way to access "0-storage" files or folders
        // eliminate double slashes, /./ and /../
        $input = PathUtility::getCanonicalPath(ltrim($input, '/'));
        if (@is_file(Environment::getPublicPath() . '/' . $input)) {
            // only the local file
            return $this->getFileObjectFromCombinedIdentifier($input);
        }
        // only the local path
        return $this->getFolderObjectFromCombinedIdentifier($input);
    }

    /**
     * Gets a folder object from an identifier [storage]:[fileId]
     *
     * @TODO check naming, inserted by SteffenR while working on filelist
     * @param string $identifier
     * @return Folder
     */
    public function getFolderObjectFromCombinedIdentifier($identifier)
    {
        $parts = GeneralUtility::trimExplode(':', $identifier);
        if (count($parts) === 2) {
            $storageUid = $parts[0];
            $folderIdentifier = $parts[1];
        } else {
            // We only got a path: Go into backwards compatibility mode and
            // use virtual Storage (uid=0)
            $storageUid = 0;

            // please note that getStorageObject() might modify $folderIdentifier when
            // auto-detecting the best-matching storage to use
            $folderIdentifier = $parts[0];
            // make sure to not use an absolute path, and remove Environment::getPublicPath if it is prepended
            if (GeneralUtility::isFirstPartOfStr($folderIdentifier, Environment::getPublicPath() . '/')) {
                $folderIdentifier = PathUtility::stripPathSitePrefix($parts[0]);
            }
        }
        return $this->getStorageObject($storageUid, [], $folderIdentifier)->getFolder($folderIdentifier);
    }

    /**
     * Gets a storage object from a combined identifier
     *
     * @param string $identifier An identifier of the form [storage uid]:[object identifier]
     * @return ResourceStorage
     */
    public function getStorageObjectFromCombinedIdentifier($identifier)
    {
        $parts = GeneralUtility::trimExplode(':', $identifier);
        $storageUid = count($parts) === 2 ? $parts[0] : null;
        return $this->getStorageObject($storageUid);
    }

    /**
     * Gets a file or folder object.
     *
     * @param string $identifier
     *
     * @throws Exception\ResourceDoesNotExistException
     * @return FileInterface|Folder
     */
    public function getObjectFromCombinedIdentifier($identifier)
    {
        list($storageId, $objectIdentifier) = GeneralUtility::trimExplode(':', $identifier);
        $storage = $this->getStorageObject($storageId);
        if ($storage->hasFile($objectIdentifier)) {
            return $storage->getFile($objectIdentifier);
        }
        if ($storage->hasFolder($objectIdentifier)) {
            return $storage->getFolder($objectIdentifier);
        }
        throw new Exception\ResourceDoesNotExistException('Object with identifier "' . $identifier . '" does not exist in storage', 1329647780);
    }

    /**
     * Creates a file object from an array of file data. Requires a database
     * row to be fetched.
     *
     * @param array $fileData
     * @param ResourceStorage $storage
     * @return File
     */
    public function createFileObject(array $fileData, ResourceStorage $storage = null)
    {
        /** @var File $fileObject */
        if (array_key_exists('storage', $fileData) && MathUtility::canBeInterpretedAsInteger($fileData['storage'])) {
            $storageObject = $this->getStorageObject((int)$fileData['storage']);
        } elseif ($storage !== null) {
            $storageObject = $storage;
            $fileData['storage'] = $storage->getUid();
        } else {
            throw new \RuntimeException('A file needs to reside in a Storage', 1381570997);
        }
        $fileObject = GeneralUtility::makeInstance(File::class, $fileData, $storageObject);
        return $fileObject;
    }

    /**
     * Creates an instance of a FileReference object. The $fileReferenceData can
     * be supplied to increase performance.
     *
     * @param int $uid The uid of the file usage (sys_file_reference) to instantiate.
     * @param array $fileReferenceData The record row from database.
     * @param bool $raw Whether to get raw results without performing overlays
     * @return FileReference
     * @throws \InvalidArgumentException
     * @throws Exception\ResourceDoesNotExistException
     */
    public function getFileReferenceObject($uid, array $fileReferenceData = [], $raw = false)
    {
        if (!is_numeric($uid)) {
            throw new \InvalidArgumentException(
                'The reference UID for the file (sys_file_reference) has to be numeric. UID given: "' . $uid . '"',
                1300086584
            );
        }
        if (!$this->fileReferenceInstances[$uid]) {
            // Fetches data in case $fileData is empty
            if (empty($fileReferenceData)) {
                $fileReferenceData = $this->getFileReferenceData($uid, $raw);
                if (!is_array($fileReferenceData)) {
                    throw new Exception\ResourceDoesNotExistException(
                        'No file reference (sys_file_reference) was found for given UID: "' . $uid . '"',
                        1317178794
                    );
                }
            }
            $this->fileReferenceInstances[$uid] = $this->createFileReferenceObject($fileReferenceData);
        }
        return $this->fileReferenceInstances[$uid];
    }

    /**
     * Creates a file usage object from an array of fileReference data
     * from sys_file_reference table.
     * Requires a database row to be already fetched and present.
     *
     * @param array $fileReferenceData
     * @return FileReference
     */
    public function createFileReferenceObject(array $fileReferenceData)
    {
        /** @var FileReference $fileReferenceObject */
        $fileReferenceObject = GeneralUtility::makeInstance(FileReference::class, $fileReferenceData);
        return $fileReferenceObject;
    }

    /**
     * Gets data for the given uid of the file reference record.
     *
     * @param int $uid The uid of the file usage (sys_file_reference) to be fetched
     * @param bool $raw Whether to get raw results without performing overlays
     * @return array|null
     */
    protected function getFileReferenceData($uid, $raw = false)
    {
        if (!$raw && TYPO3_MODE === 'BE') {
            $fileReferenceData = BackendUtility::getRecordWSOL('sys_file_reference', $uid);
        } elseif (!$raw && is_object($GLOBALS['TSFE'])) {
            $fileReferenceData = $GLOBALS['TSFE']->sys_page->checkRecord('sys_file_reference', $uid);
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $fileReferenceData = $queryBuilder->select('*')
                ->from('sys_file_reference')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();
        }
        return $fileReferenceData;
    }

    /**
     * Returns an instance of the FileIndexRepository
     *
     * @return FileIndexRepository
     */
    protected function getFileIndexRepository()
    {
        return FileIndexRepository::getInstance();
    }

    /**
     * Returns an instance of the ProcessedFileRepository
     *
     * @return ProcessedFileRepository
     */
    protected function getProcessedFileRepository()
    {
        return GeneralUtility::makeInstance(ProcessedFileRepository::class);
    }

    /**
     * Returns an instance of the Indexer
     *
     * @param ResourceStorage $storage
     * @return Index\Indexer
     */
    protected function getIndexer(ResourceStorage $storage)
    {
        return GeneralUtility::makeInstance(Index\Indexer::class, $storage);
    }
}
