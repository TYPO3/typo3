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

use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for accessing the file mounts
 */
class StorageRepository extends AbstractRepository
{
    /**
     * @var NULL|array‚
     */
    protected static $storageRowCache = null;

    /**
     * @var string
     */
    protected $objectType = ResourceStorage::class;

    /**
     * @var string
     */
    protected $table = 'sys_file_storage';

    /**
     * @var string
     */
    protected $typeField = 'driver';

    /**
     * @var string
     */
    protected $driverField = 'driver';

    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;

    public function __construct()
    {
        parent::__construct();

        /** @var $logManager LogManager */
        $logManager = GeneralUtility::makeInstance(LogManager::class);
        $this->logger = $logManager->getLogger(__CLASS__);
    }

    /**
     * @param int $uid
     *
     * @return ResourceStorage|null
     */
    public function findByUid($uid)
    {
        $this->initializeLocalCache();
        if (isset(self::$storageRowCache[$uid])) {
            return $this->factory->getStorageObject($uid, self::$storageRowCache[$uid]);
        }
        return null;
    }

    /**
     * Initializes the Storage
     */
    protected function initializeLocalCache()
    {
        if (static::$storageRowCache === null) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($this->table);

            if ($this->getEnvironmentMode() === 'FE' && !empty($GLOBALS['TSFE']->sys_page)) {
                $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
            }

            $result = $queryBuilder
                ->select('*')
                ->from($this->table)
                ->orderBy('name')
                ->execute();

            static::$storageRowCache = [];
            while ($row = $result->fetch()) {
                if (!empty($row['uid'])) {
                    static::$storageRowCache[$row['uid']] = $row;
                }
            }

            // if no storage is created before or the user has not access to a storage
            // static::$storageRowCache would have the value array()
            // so check if there is any record. If no record is found, create the fileadmin/ storage
            // selecting just one row is enough

            if (static::$storageRowCache === []) {
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable($this->table);

                $storageObjectsCount = $connection->count('uid', $this->table, []);

                if ($storageObjectsCount === 0) {
                    if ($this->createLocalStorage(
                        'fileadmin/ (auto-created)',
                        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'],
                        'relative',
                        'This is the local fileadmin/ directory. This storage mount has been created automatically by TYPO3.',
                        true
                    ) > 0) {
                        // reset to null to force reloading of storages
                        static::$storageRowCache = null;
                        // call self for initialize Cache
                        $this->initializeLocalCache();
                    }
                }
            }
        }
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

        /** @var $driverRegistry Driver\DriverRegistry */
        $driverRegistry = GeneralUtility::makeInstance(Driver\DriverRegistry::class);

        $storageObjects = [];
        foreach (static::$storageRowCache as $storageRow) {
            if ($storageRow['driver'] !== $storageType) {
                continue;
            }
            if ($driverRegistry->driverExists($storageRow['driver'])) {
                $storageObjects[] = $this->factory->getStorageObject($storageRow['uid'], $storageRow);
            } else {
                $this->logger->warning(
                    sprintf('Could not instantiate storage "%s" because of missing driver.', [$storageRow['name']]),
                    $storageRow
                );
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

        /** @var $driverRegistry Driver\DriverRegistry */
        $driverRegistry = GeneralUtility::makeInstance(Driver\DriverRegistry::class);

        $storageObjects = [];
        foreach (static::$storageRowCache as $storageRow) {
            if ($driverRegistry->driverExists($storageRow['driver'])) {
                $storageObjects[] = $this->factory->getStorageObject($storageRow['uid'], $storageRow);
            } else {
                $this->logger->warning(
                    sprintf('Could not instantiate storage "%s" because of missing driver.', [$storageRow['name']]),
                    $storageRow
                );
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
        $caseSensitive = $this->testCaseSensitivity($pathType === 'relative' ? PATH_site . $basePath : $basePath);
        // create the FlexForm for the driver configuration
        $flexFormData = [
            'data' => [
                'sDEF' => [
                    'lDEF' => [
                        'basePath' => ['vDEF' => rtrim($basePath, '/') . '/'],
                        'pathType' => ['vDEF' => $pathType],
                        'caseSensitive' => ['vDEF' => $caseSensitive]
                    ]
                ]
            ]
        ];

        /** @var $flexObj FlexFormTools */
        $flexObj = GeneralUtility::makeInstance(FlexFormTools::class);
        $flexFormXml = $flexObj->flexArray2Xml($flexFormData, true);

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
            'is_default' => $default ? 1 : 0
        ];

        $dbConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->table);
        $dbConnection->insert($this->table, $field_values);

        return (int)$dbConnection->lastInsertId($this->table);
    }

    /**
     * Creates an object managed by this repository.
     *
     * @param array $databaseRow
     * @return ResourceStorage
     */
    protected function createDomainObject(array $databaseRow)
    {
        return $this->factory->getStorageObject($databaseRow['uid'], $databaseRow);
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
}
