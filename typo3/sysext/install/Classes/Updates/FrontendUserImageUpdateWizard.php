<?php
namespace TYPO3\CMS\Install\Updates;

/**
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
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Upgrade wizard which goes through all files referenced in fe_users::image
 * and creates sys_file records as well as sys_file_reference records for each hit.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class FrontendUserImageUpdateWizard implements UpgradeWizardInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Number of records fetched per database query
     * Used to prevent memory overflows for huge databases
     */
    const RECORDS_PER_QUERY = 1000;

    /**
     * @var ResourceStorage
     */
    protected $storage;

    /**
     * Table to migrate records from
     *
     * @var string
     */
    protected $table = 'fe_users';

    /**
     * Table field holding the migration to be
     *
     * @var string
     */
    protected $fieldToMigrate = 'image';

    /**
     * the source file resides here
     *
     * @var string
     */
    protected $sourcePath = 'uploads/pics/';

    /**
     * target folder after migration
     * Relative to fileadmin
     *
     * @var string
     */
    protected $targetPath = '_migrated/frontend_users/';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $registryNamespace = 'FrontendUserImageUpdateWizard';

    /**
     * @var array
     */
    protected $recordOffset = [];

    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'frontendUserImageUpdateWizard';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Migrate all file relations from fe_users.image to sys_file_references';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'This update wizard goes through all files that are referenced in the fe_users.image'
            . ' field and adds the files to the FAL File Index. It also moves the files from'
            . ' uploads/ to the fileadmin/_migrated/ path.';
    }

    /**
     * Checks if an update is needed
     *
     * @return bool TRUE if an update is needed, FALSE otherwise
     */
    public function updateNecessary(): bool
    {
        $this->registry = GeneralUtility::makeInstance(Registry::class);
        return $this->registry->get($this->registryNamespace, 'recordOffset') === null;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Performs the database update.
     *
     * @return bool TRUE on success, FALSE on error
     */
    public function executeUpdate(): bool
    {
        try {
            $this->init();
            if (!isset($this->recordOffset[$this->table])) {
                $this->recordOffset[$this->table] = 0;
            }
            do {
                $limit = $this->recordOffset[$this->table] . ',' . self::RECORDS_PER_QUERY;
                $records = $this->getRecordsFromTable($limit);
                foreach ($records as $record) {
                    $this->migrateField($record);
                }
                $this->registry->set($this->registryNamespace, 'recordOffset', $this->recordOffset);
            } while (count($records) === self::RECORDS_PER_QUERY);

            $this->registry->remove($this->registryNamespace, 'recordOffset');
        } catch (\Exception $e) {
            // Silently catch db errors
        }
        return true;
    }

    /**
     * Initialize the storage repository.
     */
    protected function init()
    {
        $storages = GeneralUtility::makeInstance(StorageRepository::class)->findAll();
        $this->storage = $storages[0];
        $this->registry = GeneralUtility::makeInstance(Registry::class);
        $this->recordOffset = $this->registry->get($this->registryNamespace, 'recordOffset', []);
    }

    /**
     * Get records from table where the field to migrate is not empty (NOT NULL and != '')
     * and also not numeric (which means that it is migrated)
     *
     * @param int $limit Maximum number records to select
     * @return array
     * @throws \RuntimeException
     */
    protected function getRecordsFromTable($limit)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        try {
            return $queryBuilder
                ->select('uid', 'pid', $this->fieldToMigrate)
                ->from($this->table)
                ->where(
                    $queryBuilder->expr()->isNotNull($this->fieldToMigrate),
                    $queryBuilder->expr()->neq(
                        $this->fieldToMigrate,
                        $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->comparison(
                        'CAST(CAST(' . $queryBuilder->quoteIdentifier($this->fieldToMigrate) . ' AS DECIMAL) AS CHAR)',
                        ExpressionBuilder::NEQ,
                        'CAST(' . $queryBuilder->quoteIdentifier($this->fieldToMigrate) . ' AS CHAR)'
                    )
                )
                ->orderBy('uid')
                ->setFirstResult($limit)
                ->execute()
                ->fetchAll();
        } catch (DBALException $e) {
            throw new \RuntimeException(
                'Database query failed. Error was: ' . $e->getPrevious()->getMessage(),
                1476050084
            );
        }
    }

    /**
     * Migrates a single field.
     *
     * @param array $row
     */
    protected function migrateField($row)
    {
        $fieldItems = GeneralUtility::trimExplode(',', $row[$this->fieldToMigrate], true);
        if (empty($fieldItems) || is_numeric($row[$this->fieldToMigrate])) {
            return;
        }
        $fileadminDirectory = rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') . '/';
        $i = 0;

        $storageUid = (int)$this->storage->getUid();

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        foreach ($fieldItems as $item) {
            $fileUid = null;
            $sourcePath = Environment::getPublicPath() . '/' . $this->sourcePath . $item;
            $targetDirectory = Environment::getPublicPath() . '/' . $fileadminDirectory . $this->targetPath;
            $targetPath = $targetDirectory . PathUtility::basename($item);

            // maybe the file was already moved, so check if the original file still exists
            if (file_exists($sourcePath)) {
                if (!is_dir($targetDirectory)) {
                    GeneralUtility::mkdir_deep($targetDirectory);
                }

                // see if the file already exists in the storage
                $fileSha1 = sha1_file($sourcePath);

                $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file');
                $queryBuilder->getRestrictions()->removeAll();
                $existingFileRecord = $queryBuilder->select('uid')->from('sys_file')->where(
                    $queryBuilder->expr()->eq(
                        'sha1',
                        $queryBuilder->createNamedParameter($fileSha1, \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'storage',
                        $queryBuilder->createNamedParameter($storageUid, \PDO::PARAM_INT)
                    )
                )->execute()->fetch();

                // the file exists, the file does not have to be moved again
                if (is_array($existingFileRecord)) {
                    $fileUid = $existingFileRecord['uid'];
                } else {
                    // just move the file (no duplicate)
                    rename($sourcePath, $targetPath);
                }
            }

            if ($fileUid === null) {
                // get the File object if it hasn't been fetched before
                try {
                    // if the source file does not exist, we should just continue, but leave a message in the docs;
                    // ideally, the user would be informed after the update as well.
                    /** @var File $file */
                    $file = $this->storage->getFile($this->targetPath . $item);
                    $fileUid = $file->getUid();
                } catch (\InvalidArgumentException $e) {
                    // no file found, no reference can be set
                    $this->logger->notice(
                        'File ' . $this->sourcePath . $item . ' does not exist. Reference was not migrated.',
                        [
                            'table' => $this->table,
                            'record' => $row,
                            'field' => $this->fieldToMigrate,
                        ]
                    );
                    continue;
                }
            }

            if ($fileUid > 0) {
                $fields = [
                    'fieldname' => $this->fieldToMigrate,
                    'table_local' => 'sys_file',
                    'pid' => $this->table === 'pages' ? $row['uid'] : $row['pid'],
                    'uid_foreign' => $row['uid'],
                    'uid_local' => $fileUid,
                    'tablenames' => $this->table,
                    'crdate' => time(),
                    'tstamp' => time(),
                    'sorting_foreign' => $i,
                ];

                $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');
                $queryBuilder->insert('sys_file_reference')->values($fields)->execute();
                ++$i;
            }
        }

        // Update referencing table's original field to now contain the count of references,
        // but only if all new references could be set
        if ($i === count($fieldItems)) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable($this->table);
            $queryBuilder->update($this->table)->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($row['uid'], \PDO::PARAM_INT)
                )
            )->set($this->fieldToMigrate, $i)->execute();
        } else {
            $this->recordOffset[$this->table]++;
        }
    }
}
