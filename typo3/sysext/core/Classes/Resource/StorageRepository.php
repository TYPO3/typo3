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

namespace TYPO3\CMS\Core\Resource;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\Event\AfterResourceStorageInitializationEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeResourceStorageInitializationEvent;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Repository for accessing the file storages
 */
class StorageRepository implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array|null
     */
    protected $storageRowCache;

    /**
     * @var array<int, LocalPath>|null
     */
    protected $localDriverStorageCache;

    /**
     * @var string
     */
    protected $table = 'sys_file_storage';

    /**
     * @var DriverRegistry
     */
    protected $driverRegistry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ResourceStorage[]|null
     */
    protected $storageInstances;

    public function __construct(EventDispatcherInterface $eventDispatcher, DriverRegistry $driverRegistry)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->driverRegistry = $driverRegistry;
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
    public function getDefaultStorage(): ?ResourceStorage
    {
        $allStorages = $this->findAll();
        foreach ($allStorages as $storage) {
            if ($storage->isDefault()) {
                return $storage;
            }
        }
        return null;
    }

    public function findByUid(int $uid): ?ResourceStorage
    {
        $this->initializeLocalCache();
        if (isset($this->storageRowCache[$uid]) || $uid === 0) {
            return $this->getStorageObject($uid, $this->storageRowCache[$uid] ?? []);
        }
        return null;
    }

    /**
     * Gets a storage object from a combined identifier
     *
     * @param string $identifier An identifier of the form [storage uid]:[object identifier]
     * @return ResourceStorage|null
     */
    public function findByCombinedIdentifier(string $identifier): ?ResourceStorage
    {
        $parts = GeneralUtility::trimExplode(':', $identifier);
        return count($parts) === 2 ? $this->findByUid((int)$parts[0]) : null;
    }

    /**
     * @param int $uid
     * @return array
     */
    protected function fetchRecordDataByUid(int $uid): array
    {
        $this->initializeLocalCache();
        if (!isset($this->storageRowCache[$uid])) {
            throw new \InvalidArgumentException(sprintf('No storage found with uid "%d".', $uid), 1599235454);
        }

        return $this->storageRowCache[$uid];
    }

    /**
     * Initializes the Storage
     */
    protected function initializeLocalCache()
    {
        if ($this->storageRowCache === null) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($this->table);

            $result = $queryBuilder
                ->select('*')
                ->from($this->table)
                ->orderBy('name')
                ->executeQuery();

            $this->storageRowCache = [];
            while ($row = $result->fetchAssociative()) {
                if (!empty($row['uid'])) {
                    $this->storageRowCache[$row['uid']] = $row;
                }
            }

            // if no storage is created before or the user has not access to a storage
            // $this->storageRowCache would have the value array()
            // so check if there is any record. If no record is found, create the fileadmin/ storage
            // selecting just one row is enough

            if ($this->storageRowCache === []) {
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable($this->table);

                $storageObjectsCount = $connection->count('uid', $this->table, []);

                if ($storageObjectsCount === 0) {
                    if ($this->createLocalStorage(
                        rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] ?? 'fileadmin', '/'),
                        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'],
                        'relative',
                        'This is the local fileadmin/ directory. This storage mount has been created automatically by TYPO3.',
                        true
                    ) > 0) {
                        // clear Cache to force reloading of storages
                        $this->flush();
                        // call self for initialize Cache
                        $this->initializeLocalCache();
                    }
                }
            }
        }
    }

    /**
     * Flush the internal storage caches to force reloading of storages with the next fetch.
     *
     * @internal
     */
    public function flush(): void
    {
        $this->storageRowCache = null;
        $this->storageInstances = null;
        $this->localDriverStorageCache = null;
    }

    /**
     * Finds storages by type, i.e. the driver used
     *
     * @param string $storageType
     * @return ResourceStorage[]
     */
    public function findByStorageType($storageType)
    {
        $this->initializeLocalCache();

        $storageObjects = [];
        foreach ($this->storageRowCache as $storageRow) {
            if ($storageRow['driver'] !== $storageType) {
                continue;
            }
            if ($this->driverRegistry->driverExists($storageRow['driver'])) {
                $storageObjects[] = $this->getStorageObject($storageRow['uid'], $storageRow);
            } else {
                $this->logger->warning('Could not instantiate storage "{name}" because of missing driver.', ['name' => $storageRow['name']]);
            }
        }
        return $storageObjects;
    }

    /**
     * Returns a list of mountpoints that are available in the VFS.
     * In case no storage exists this automatically created a storage for fileadmin/
     *
     * @return ResourceStorage[]
     */
    public function findAll()
    {
        $this->initializeLocalCache();

        $storageObjects = [];
        foreach ($this->storageRowCache as $storageRow) {
            if ($this->driverRegistry->driverExists($storageRow['driver'])) {
                $storageObjects[] = $this->getStorageObject($storageRow['uid'], $storageRow);
            } else {
                $this->logger->warning('Could not instantiate storage "{name}" because of missing driver.', ['name' => $storageRow['name']]);
            }
        }
        return $storageObjects;
    }

    /**
     * Create the initial local storage base e.g. for the fileadmin/ directory.
     *
     * @param string $name
     * @param string $basePath
     * @param string $pathType
     * @param string $description
     * @param bool $default set to default storage
     * @return int uid of the inserted record
     */
    public function createLocalStorage($name, $basePath, $pathType, $description = '', $default = false)
    {
        $caseSensitive = $this->testCaseSensitivity($pathType === 'relative' ? Environment::getPublicPath() . '/' . $basePath : $basePath);
        // create the FlexForm for the driver configuration
        $flexFormData = [
            'data' => [
                'sDEF' => [
                    'lDEF' => [
                        'basePath' => ['vDEF' => rtrim($basePath, '/') . '/'],
                        'pathType' => ['vDEF' => $pathType],
                        'caseSensitive' => ['vDEF' => $caseSensitive],
                    ],
                ],
            ],
        ];

        $flexFormXml = GeneralUtility::makeInstance(FlexFormTools::class)->flexArray2Xml($flexFormData, true);

        // create the record
        $field_values = [
            'pid' => 0,
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
            'name' => $name,
            'description' => $description,
            'driver' => 'Local',
            'configuration' => $flexFormXml,
            'is_online' => 1,
            'is_browsable' => 1,
            'is_public' => 1,
            'is_writable' => 1,
            'is_default' => $default ? 1 : 0,
        ];

        $dbConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->table);
        $dbConnection->insert($this->table, $field_values);

        // Flush local resourceStorage cache so the storage can be accessed during the same request right away
        $this->flush();

        return (int)$dbConnection->lastInsertId($this->table);
    }

    /**
     * Test if the local filesystem is case sensitive
     *
     * @param string $absolutePath
     * @return bool
     */
    protected function testCaseSensitivity($absolutePath)
    {
        $caseSensitive = true;
        $path = rtrim($absolutePath, '/') . '/aAbB';
        $testFileExists = @file_exists($path);

        // create test file
        if (!$testFileExists) {
            // @todo: This misses a test for directory existence, touch does not create
            //        dirs. StorageRepositoryTest stumbles here. It should at least be
            //        sanitized to not touch() a file in a non-existing directory.
            touch($path);
        }

        // do the actual sensitivity check
        if (@file_exists(strtoupper($path)) && @file_exists(strtolower($path))) {
            $caseSensitive = false;
        }

        // clean filesystem
        if (!$testFileExists) {
            unlink($path);
        }

        return $caseSensitive;
    }

    /**
     * Creates an instance of the storage from given UID. The $recordData can
     * be supplied to increase performance.
     *
     * @param int $uid The uid of the storage to instantiate.
     * @param array $recordData The record row from database.
     * @param mixed|null $fileIdentifier Identifier for a file. Used for auto-detection of a storage, but only if $uid === 0 (Local default storage) is used
     * @throws \InvalidArgumentException
     * @return ResourceStorage
     */
    public function getStorageObject($uid, array $recordData = [], &$fileIdentifier = null): ResourceStorage
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
            /** @var BeforeResourceStorageInitializationEvent $event */
            $event = $this->eventDispatcher->dispatch(new BeforeResourceStorageInitializationEvent($uid, $recordData, $fileIdentifier));
            $recordData = $event->getRecord();
            $uid = $event->getStorageUid();
            $fileIdentifier = $event->getFileIdentifier();
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
                    'basePath' => Environment::getPublicPath(),
                    'pathType' => 'absolute',
                ];
            } elseif ($recordData === [] || (int)$recordData['uid'] !== $uid) {
                $recordData = $this->fetchRecordDataByUid($uid);
            }
            $storageObject = $this->createStorageObject($recordData, $storageConfiguration);
            $storageObject = $this->eventDispatcher
                ->dispatch(new AfterResourceStorageInitializationEvent($storageObject))
                ->getStorage();
            $this->storageInstances[$uid] = $storageObject;
        }
        return $this->storageInstances[$uid];
    }

    /**
     * Checks whether a file resides within a real storage in local file system.
     * If no match is found, uid 0 is returned which is a fallback storage pointing to fileadmin in public web path.
     *
     * The file identifier is adapted accordingly to match the new storage's base path.
     *
     * @param string $localPath
     * @return int
     */
    protected function findBestMatchingStorageByLocalPath(&$localPath): int
    {
        if ($this->localDriverStorageCache === null) {
            $this->initializeLocalStorageCache();
        }
        // normalize path information (`//`, `../`)
        $localPath = PathUtility::getCanonicalPath($localPath);
        if (!str_starts_with($localPath, '/')) {
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
    protected function initializeLocalStorageCache(): void
    {
        $this->localDriverStorageCache = [
            // implicit legacy storage in project's public path
            0 => new LocalPath(Environment::getPublicPath(), LocalPath::TYPE_ABSOLUTE),
        ];
        $storageObjects = $this->findByStorageType('Local');
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
     * Creates a storage object from a storage database row.
     *
     * @param array $storageRecord
     * @param array|null $storageConfiguration Storage configuration (if given, this won't be extracted from the FlexForm value but the supplied array used instead)
     * @return ResourceStorage
     * @internal this method is only public for having access to ResourceFactory->createStorageObject(). In TYPO3 v12 this method can be changed to protected again.
     */
    public function createStorageObject(array $storageRecord, array $storageConfiguration = null): ResourceStorage
    {
        if (!$storageConfiguration && !empty($storageRecord['configuration'])) {
            $storageConfiguration = $this->convertFlexFormDataToConfigurationArray($storageRecord['configuration']);
        }
        $driverType = $storageRecord['driver'];
        $driverObject = $this->getDriverObject($driverType, (array)$storageConfiguration);
        $storageRecord['configuration'] = $storageConfiguration;
        return GeneralUtility::makeInstance(ResourceStorage::class, $driverObject, $storageRecord, $this->eventDispatcher);
    }

    /**
     * Converts a flexform data string to a flat array with key value pairs
     *
     * @param string $flexFormData
     * @return array Array with key => value pairs of the field data in the FlexForm
     */
    protected function convertFlexFormDataToConfigurationArray(string $flexFormData): array
    {
        if ($flexFormData) {
            return GeneralUtility::makeInstance(FlexFormService::class)->convertFlexFormContentToArray($flexFormData);
        }
        return [];
    }

    /**
     * Creates a driver object for a specified storage object.
     *
     * @param string $driverIdentificationString The driver class (or identifier) to use.
     * @param array $driverConfiguration The configuration of the storage
     * @return DriverInterface
     */
    protected function getDriverObject(string $driverIdentificationString, array $driverConfiguration): DriverInterface
    {
        $driverClass = $this->driverRegistry->getDriverClass($driverIdentificationString);
        /** @var DriverInterface $driverObject */
        $driverObject = GeneralUtility::makeInstance($driverClass, $driverConfiguration);
        return $driverObject;
    }

    /**
     * @param array $storageRecord
     * @return ResourceStorage
     * @internal
     */
    public function createFromRecord(array $storageRecord): ResourceStorage
    {
        return $this->createStorageObject($storageRecord);
    }
}
