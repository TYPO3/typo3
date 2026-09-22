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

use Doctrine\DBAL\Exception as DbalException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfiguration;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\Event\AfterResourceStorageInitializationEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeResourceStorageInitializationEvent;
use TYPO3\CMS\Core\Resource\Event\FileStorageConfigurationChangedEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Repository for accessing the file storages configured in config/system/file-storages.yaml
 */
#[Autoconfigure(public: true)]
class StorageRepository
{
    /**
     * @var array<positive-int, array<string, mixed>>|null
     */
    protected ?array $storageRowCache = null;

    /**
     * @var array<int<0, max>, LocalPath>|null
     */
    protected ?array $localDriverStorageCache = null;

    /**
     * @var array<int<0, max>, ResourceStorage>
     */
    protected array $storageInstances = [];

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly FileStorageConfiguration $configuration,
        protected readonly FileStorageConfigurationWriter $configurationWriter,
        protected readonly DriverRegistry $driverRegistry,
        protected readonly LoggerInterface $logger,
        protected readonly ConnectionPool $connectionPool,
        protected readonly FlexFormTools $flexFormTools,
    ) {}

    /**
     * Returns the Default Storage
     *
     * The Default Storage is considered to be the replacement for the fileadmin/ construct.
     * Without a configuration file, it is the local storage of the fileadmin/ directory.
     * getDefaultStorage->getDefaultFolder() will get you fileadmin/user_upload/ in a standard
     * TYPO3 installation.
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

    /**
     * Resolves the uid of the default storage without instantiating any storage object.
     * As with all other lookups of this repository, the default local storage is created
     * if no storage record exists yet.
     *
     * @internal Needed while file mounts are resolved, as instantiating a storage applies them.
     */
    public function getDefaultStorageUid(): ?int
    {
        $this->initializeLocalCache();
        foreach ($this->storageRowCache as $uid => $storageRow) {
            if ($storageRow['default']) {
                return $uid;
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
     * @param non-empty-string $identifier An identifier of the form [storage uid]:[object identifier]
     */
    public function findByCombinedIdentifier(string $identifier): ?ResourceStorage
    {
        $parts = GeneralUtility::trimExplode(':', $identifier);
        return count($parts) === 2 ? $this->findByUid((int)$parts[0]) : null;
    }

    protected function fetchRecordDataByUid(int $uid): array
    {
        $this->initializeLocalCache();
        if (!isset($this->storageRowCache[$uid])) {
            throw new \InvalidArgumentException(sprintf('No storage found with uid "%d".', $uid), 1599235454);
        }

        return $this->storageRowCache[$uid];
    }

    /**
     * Reads the storage definitions from the configuration, sorted by name.
     */
    protected function initializeLocalCache(): void
    {
        if ($this->storageRowCache === null) {
            $this->storageRowCache = $this->configuration->getAll();
            uasort($this->storageRowCache, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
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
        $this->storageInstances = [];
        $this->localDriverStorageCache = null;
        $this->configuration->flush();
    }

    /**
     * @internal
     */
    #[AsEventListener]
    public function onConfigurationChanged(FileStorageConfigurationChangedEvent $event): void
    {
        $this->flush();
    }

    /**
     * Finds storages by type, i.e. the driver used
     *
     * @param non-empty-string $storageType
     * @return list<ResourceStorage>
     */
    public function findByStorageType(string $storageType): array
    {
        $this->initializeLocalCache();

        $storageObjects = [];
        foreach ($this->storageRowCache as $storageRow) {
            if ($storageRow['driver'] !== $storageType) {
                continue;
            }
            if ($this->driverRegistry->driverExists($storageRow['driver'])) {
                $storageObjects[] = $this->getStorageObject($storageRow['identifier'], $storageRow);
            } else {
                $this->logger->warning('Could not instantiate storage "{name}" because of missing driver.', ['name' => $storageRow['name']]);
            }
        }
        return $storageObjects;
    }

    /**
     * Returns a list of mountpoints that are available in the VFS.
     *
     * @return list<ResourceStorage>
     */
    public function findAll(): array
    {
        $this->initializeLocalCache();

        $storageObjects = [];
        foreach ($this->storageRowCache as $storageRow) {
            if ($this->driverRegistry->driverExists($storageRow['driver'])) {
                $storageObjects[] = $this->getStorageObject($storageRow['identifier'], $storageRow);
            } else {
                $this->logger->warning('Could not instantiate storage "{name}" because of missing driver.', ['name' => $storageRow['name']]);
            }
        }
        return $storageObjects;
    }

    /**
     * Adds a local storage to config/system/file-storages.yaml, e.g. for a directory
     * next to fileadmin/. The base path is absolute or relative to the project path.
     * The file is part of the deployment: call this from command line tools or set up
     * code, not during regular web requests.
     *
     * @param non-empty-string $name
     * @param non-empty-string $basePath
     * @return positive-int the identifier of the new storage
     */
    public function addLocalStorage(string $name, string $basePath, bool $default = false): int
    {
        $localPath = LocalPath::fromDriverConfiguration(['basePath' => $basePath]);
        if ($localPath === null) {
            throw new \InvalidArgumentException('A local storage needs a base path.', 1789862416);
        }
        $projectPath = Environment::getProjectPath() . '/';
        if (str_starts_with($localPath->getAbsolute(), $projectPath)) {
            $basePath = substr($localPath->getAbsolute(), strlen($projectPath));
        } else {
            $basePath = $localPath->getAbsolute();
        }
        $this->initializeLocalCache();
        $identifier = $this->getNextIdentifier();
        $entries = [];
        if ($default) {
            $rawStorages = $this->configuration->loadRaw()['storages'] ?? [];
            foreach ($this->storageRowCache as $existingIdentifier => $storageRow) {
                if ($storageRow['default']) {
                    $existingEntry = is_array($rawStorages[$existingIdentifier] ?? null) ? $rawStorages[$existingIdentifier] : $this->toCompactEntry($storageRow);
                    $entries[$existingIdentifier] = array_replace($existingEntry, ['default' => false]);
                }
            }
        }
        $entries[$identifier] = [
            'name' => $name,
            'driver' => 'Local',
            'default' => $default,
            'configuration' => [
                'basePath' => $basePath,
            ],
        ];
        $this->configurationWriter->apply($entries);
        $this->flush();
        return $identifier;
    }

    /**
     * @param non-empty-string $name
     * @param non-empty-string $basePath
     * @param non-empty-string $pathType "relative" to the public path, or "absolute"
     * @return positive-int the identifier of the new storage
     * @deprecated since TYPO3 v15, will be removed in TYPO3 v16. Use addLocalStorage() instead.
     */
    public function createLocalStorage(string $name, string $basePath, string $pathType, string $description = '', bool $default = false): int
    {
        trigger_error(
            'StorageRepository->createLocalStorage() is deprecated and will be removed in TYPO3 v16. Use StorageRepository->addLocalStorage() instead, which resolves relative base paths against the project path.',
            E_USER_DEPRECATED
        );
        if ($pathType === 'relative') {
            $basePath = Environment::getPublicPath() . '/' . ltrim($basePath, '/');
        }
        return $this->addLocalStorage($name, $basePath, $default);
    }

    /**
     * Identifiers are never handed out twice: files, processed files, file mounts
     * and folder collections of a removed storage may still reference its identifier.
     *
     * @return positive-int
     */
    protected function getNextIdentifier(): int
    {
        $highestIdentifier = $this->storageRowCache === [] ? 0 : max(array_keys($this->storageRowCache));
        try {
            $highestIdentifier = max(
                $highestIdentifier,
                $this->getHighestReferencedIdentifier('sys_file', 'storage'),
                $this->getHighestReferencedIdentifier('sys_file_processedfile', 'storage'),
                $this->getHighestIdentifierOfCombinedIdentifiers('sys_filemounts', 'identifier'),
                $this->getHighestIdentifierOfCombinedIdentifiers('sys_file_collection', 'folder_identifier'),
            );
        } catch (DbalException) {
            // No database during set up, so nothing references a storage yet
        }
        return $highestIdentifier + 1;
    }

    private function getHighestReferencedIdentifier(string $tableName, string $fieldName): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->selectLiteral('MAX(' . $queryBuilder->quoteIdentifier($fieldName) . ')')
            ->from($tableName)
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Combined identifiers like "2:/folder/" start with the storage identifier.
     */
    private function getHighestIdentifierOfCombinedIdentifiers(string $tableName, string $fieldName): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select($fieldName)
            ->from($tableName)
            ->executeQuery();
        $highestIdentifier = 0;
        while (($combinedIdentifier = $result->fetchOne()) !== false) {
            if (preg_match('/^([0-9]+):/', (string)$combinedIdentifier, $matches)) {
                $highestIdentifier = max($highestIdentifier, (int)$matches[1]);
            }
        }
        return $highestIdentifier;
    }

    /**
     * Creates an instance of the storage from given UID. The $recordData can
     * be supplied to increase performance.
     *
     * @param int<0, max>|string $uid The uid of the storage to instantiate.
     * @param array<string, mixed> $recordData The normalized storage definition from the configuration.
     * @param non-empty-string|null $fileIdentifier Identifier for a file. Used for auto-detection of a storage, but only if $uid === 0 (Local default storage) is used
     * @param-out string $fileIdentifier
     */
    public function getStorageObject(int|string $uid, array $recordData = [], ?string &$fileIdentifier = null): ResourceStorage
    {
        $uid = (int)$uid;
        if ($uid === 0 && $fileIdentifier !== null) {
            $uid = $this->findBestMatchingStorageByLocalPath($fileIdentifier);
        }
        if (!isset($this->storageInstances[$uid])) {
            $event = $this->eventDispatcher->dispatch(new BeforeResourceStorageInitializationEvent($uid, $recordData, $fileIdentifier));
            $recordData = $this->migrateLegacyRecordKeys($event->getRecord());
            $uid = $event->getStorageUid();
            $fileIdentifier = $event->getFileIdentifier();
            // If the built-in storage with UID=0 is requested:
            if ($uid === 0) {
                $recordData = [
                    'identifier' => 0,
                    'name' => 'Fallback Storage',
                    'driver' => 'Local',
                    'default' => false,
                    'browsable' => true,
                    'public' => true,
                    'writable' => true,
                    'online' => true,
                    'autoExtractMetadata' => false,
                    'processingFolder' => 'typo3temp/assets/_processed_/',
                    'configuration' => [
                        'basePath' => Environment::getPublicPath(),
                        // Identifiers of this storage were never case-folded, independent of the file system
                        'caseSensitive' => true,
                    ],
                ];
            } elseif ($recordData === [] || (int)($recordData['identifier'] ?? -1) !== $uid) {
                $recordData = $this->fetchRecordDataByUid($uid);
            }
            $storageObject = $this->createStorageObject($recordData);
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
     * @internal absolutely do not call this method publicly, not even in TYPO3 core. It must only be used for legacy resource resolving
     * @param non-empty-string $localPath
     * @param-out string $localPath
     * @return int<0, max>
     */
    public function findBestMatchingStorageByLocalPath(string &$localPath): int
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
            $localPath = LocalPath::fromDriverConfiguration($localStorage->getConfiguration());
            if ($localPath === null) {
                continue;
            }
            $this->localDriverStorageCache[$localStorage->getUid()] = $localPath;
        }
    }

    /**
     * Creates a storage object from a normalized storage definition.
     *
     * @param array<string, mixed> $storageRecord
     */
    protected function createStorageObject(array $storageRecord): ResourceStorage
    {
        $driverObject = $this->getDriverObject((string)$storageRecord['driver'], (array)($storageRecord['configuration'] ?? []));
        return GeneralUtility::makeInstance(ResourceStorage::class, $driverObject, $storageRecord, $this->eventDispatcher);
    }

    /**
     * Creates a driver object for a specified storage object.
     *
     * @param non-empty-string $driverIdentificationString The driver class (or identifier) to use.
     * @param array $driverConfiguration The configuration of the storage
     */
    protected function getDriverObject(string $driverIdentificationString, array $driverConfiguration): DriverInterface
    {
        $driverClass = $this->driverRegistry->getDriverClass($driverIdentificationString);
        /** @var DriverInterface $driverObject */
        $driverObject = GeneralUtility::makeInstance($driverClass, $driverConfiguration);
        return $driverObject;
    }

    /**
     * Creates a storage object from a storage definition that is not part of the
     * configuration, e.g. for checks. The definition is normalized first.
     *
     * @param array<string, mixed> $storageRecord
     * @internal
     */
    public function createFromRecord(array $storageRecord): ResourceStorage
    {
        $identifier = (int)($storageRecord['identifier'] ?? 0);
        unset($storageRecord['identifier']);
        $normalized = $this->configuration->normalize(max($identifier, 1), $storageRecord);
        $normalized['identifier'] = $identifier;
        return $this->createStorageObject($normalized);
    }

    /**
     * Listeners of BeforeResourceStorageInitializationEvent may still set the keys of
     * the former sys_file_storage row. They are mapped to the normalized definition.
     *
     * @deprecated since TYPO3 v15, will be removed in TYPO3 v16 together with the mapping.
     */
    protected function migrateLegacyRecordKeys(array $recordData): array
    {
        $legacyKeys = [
            'uid' => 'identifier',
            'is_default' => 'default',
            'is_browsable' => 'browsable',
            'is_public' => 'public',
            'is_writable' => 'writable',
            'is_online' => 'online',
            'auto_extract_metadata' => 'autoExtractMetadata',
            'processingfolder' => 'processingFolder',
        ];
        $usedLegacyKeys = array_intersect_key($legacyKeys, $recordData);
        $hasLegacyConfiguration = is_string($recordData['configuration'] ?? null);
        if ($usedLegacyKeys === [] && !$hasLegacyConfiguration) {
            return $recordData;
        }
        trigger_error(
            'Setting the keys of the former sys_file_storage row ("' . implode('", "', array_keys($usedLegacyKeys)) . '") or a FlexForm string as "configuration" in BeforeResourceStorageInitializationEvent is deprecated and will stop working in TYPO3 v16. Use the keys of the normalized storage definition instead.',
            E_USER_DEPRECATED
        );
        foreach ($usedLegacyKeys as $legacyKey => $key) {
            $value = $recordData[$legacyKey];
            unset($recordData[$legacyKey]);
            $recordData[$key] = match ($key) {
                'identifier' => (int)$value,
                'processingFolder' => (string)$value,
                default => (bool)$value,
            };
        }
        if ($hasLegacyConfiguration) {
            $recordData['configuration'] = $this->flexFormTools->convertFlexFormContentToArray($recordData['configuration']);
        }
        unset($recordData['pid'], $recordData['description'], $recordData['tstamp'], $recordData['crdate'], $recordData['deleted']);
        return $recordData;
    }

    /**
     * @param array<string, mixed> $storageRow normalized definition
     * @return array<string, mixed> the definition without the identifier, as the writer expects it
     */
    protected function toCompactEntry(array $storageRow): array
    {
        unset($storageRow['identifier']);
        return $storageRow;
    }
}
